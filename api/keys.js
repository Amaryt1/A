// تخزين مؤقت في الذاكرة (يدعم ربط Upstash Redis اختياريًا)
let memoryKeys = [
  { code: "AMARYT", type: "lifetime", days: 3, created: Date.now() },
  { code: "AAMW", type: "monthly", days: 30, created: Date.now() }
];

export default async function handler(req, res) {
  // تفعيل CORS للسماح بالاتصال من أي مكان
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');

  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  const { UPSTASH_REDIS_REST_URL, UPSTASH_REDIS_REST_TOKEN } = process.env;

  // جلب البيانات من Redis إن وجد أو من الذاكرة
  async function getKeys() {
    if (UPSTASH_REDIS_REST_URL && UPSTASH_REDIS_REST_TOKEN) {
      try {
        const response = await fetch(`${UPSTASH_REDIS_REST_URL}/get/valid_keys_store`, {
          headers: { Authorization: `Bearer ${UPSTASH_REDIS_REST_TOKEN}` }
        });
        const data = await response.json();
        return data.result ? JSON.parse(data.result) : memoryKeys;
      } catch (e) {
        return memoryKeys;
      }
    }
    return memoryKeys;
  }

  // حفظ البيانات
  async function saveKeys(keys) {
    memoryKeys = keys;
    if (UPSTASH_REDIS_REST_URL && UPSTASH_REDIS_REST_TOKEN) {
      try {
        await fetch(`${UPSTASH_REDIS_REST_URL}/set/valid_keys_store`, {
          method: 'POST',
          headers: { Authorization: `Bearer ${UPSTASH_REDIS_REST_TOKEN}` },
          body: JSON.stringify(JSON.stringify(keys))
        });
      } catch (e) {}
    }
  }

  // 1. طلب GET القادم من أداة الآيفون (Tweak.m)
  if (req.method === 'GET') {
    const keys = await getKeys();
    const validCodes = keys.map(k => k.code);

    return res.status(200).json({
      valid_keys: validCodes,
      details: keys
    });
  }

  // 2. طلب POST لإنشاء مفاتيح جديدة من لوحة التحكم
  if (req.method === 'POST') {
    const { action, keyData, adminPass } = req.body || {};

    if (adminPass !== "123456") { // كلمة سر اللوحة
      return res.status(401).json({ error: "كلمة المرور غير صحيحة" });
    }

    let currentKeys = await getKeys();

    if (action === 'ADD') {
      currentKeys.push(keyData);
      await saveKeys(currentKeys);
      return res.status(200).json({ success: true, keys: currentKeys });
    }

    if (action === 'DELETE') {
      currentKeys = currentKeys.filter(k => k.code !== keyData.code);
      await saveKeys(currentKeys);
      return res.status(200).json({ success: true, keys: currentKeys });
    }
  }

  return res.status(405).json({ error: "Method not allowed" });
}
