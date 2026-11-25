// ═══════════════════════════════════════════════════════════
// CONFIG (Obfuscated Tokens + Env Var Override)
// ═══════════════════════════════════════════════════════════
//
// 🔒 برای امنیت کامل، این مقادیر را در Environment Secrets
//    ورکر ست کن (نام‌ها): BOT_TOKEN, GH_TOKEN,
//    ADMIN_PANEL_PASS, ENC_KEY
//    اگر ست نشوند، از مقادیر رمزگذاری‌شده زیر استفاده می‌شود.
//
function _dc(a, o) { return a.map(c => String.fromCharCode(c + o)).join(""); }

// ─── توکن‌ها به صورت آرایه کد ASCII (آفست +30) ───
const _OB = {
  BOT_TOKEN: [
    37,43,36,37,41,41,18,52,41,57,47,47,44,40,41,48,
    58,47,41,51,52,55,47,40,35,54,39,54,48,57,38,37,
    44,45,40,58,45,60,43,56,42,59,50,51,45,52,41,45,
    46,41,55,38,60,42,47,52,52,58,57,37,48,38,55,36
  ],
  GH_TOKEN: [
    73,74,82,65,35,82,84,75,49,85,80,69,67,73,41,80,
    70,35,82,73,27,39,23,36,92,37,21,69,47,40,73,36,
    26,35,22,36,23,24,84,85
  ],
  ADMIN_PANEL_PASS: [52,71,73,80,67,84,35,70,79,75,80,20,18,20,22,3],
  ENC_KEY: [
    52,21,73,80,22,84,65,55,78,86,84,67,65,53,21,69,
    84,21,86,65,20,18,20,22,3
  ]
};

// ─── مقادیر Runtime ───
let BOT_TOKEN, GH_TOKEN, ADMIN_PANEL_PASS, ENC_KEY;

function loadConfig(env) {
  env = env || {};
  BOT_TOKEN        = env.BOT_TOKEN        || _dc(_OB.BOT_TOKEN, 30);
  GH_TOKEN         = env.GH_TOKEN         || _dc(_OB.GH_TOKEN, 30);
  ADMIN_PANEL_PASS = env.ADMIN_PANEL_PASS || _dc(_OB.ADMIN_PANEL_PASS, 30);
  ENC_KEY          = env.ENC_KEY          || _dc(_OB.ENC_KEY, 30);
}

// ─── مقادیر غیرحساس (بدون رمزگذاری) ───
const CHANNEL_ID = 'c0CguJn05c1fb66c2a0e5cffbad275dd';
const CHANNEL_USERNAME = '@SampRegnar';
const CHANNEL_LINK = 'https://rubika.ir/SampRegnar';

const GH_OWNER = 'MamadRegnar';
const GH_REPO = 'Shop';
const GH_BRANCH = 'main';
const GH_PATH = 'vip_accounts.json';
const GH_USERS_PATH = 'bot_users.json';
const GH_STATE_PATH = 'bot_states.json';

const ENC_SEED = 1337;
const PLAN_DURATION_MIN = 60;
const ADMIN_ID = 'u0K8dTo0715281e83f6914f84fa57791';

const BTN_JOIN_CHANNEL_TEXT = '🔗 عضویت در کانال';
const BTN_JOIN_CHANNEL_ID = 'btn_join_channel';
const BTN_GET_PLAN_TEXT = '🎁 دریافت پلن رایگان';
const BTN_GET_PLAN_ID = 'btn_get_plan';
const BTN_CHECK_JOIN_TEXT = '✅ عضو شدم، بررسی کن';
const BTN_CHECK_JOIN_ID = 'btn_check_join';
const BTN_BACK_TEXT = '🔙 بازگشت';
const BTN_BACK_ID = 'btn_back_to_main';

const ADM_BROADCAST = 'adm_broadcast';
const ADM_CREATE = 'adm_create';
const ADM_RESET = 'adm_reset';
const ADM_STATS = 'adm_stats';
const ADM_LIST = 'adm_list';

// ═══════════════════════════════════════════════════════════
// MAIN FETCH HANDLER
// ═══════════════════════════════════════════════════════════
export default {
  async fetch(request, env, ctx) {
    // 🔑 بارگذاری کانفیگ (Env یا Fallback رمزگذاری‌شده)
    loadConfig(env);

    const url = new URL(request.url);

    // 1. Webhook Setup Route
    if (url.searchParams.get("setup") === "1" && url.searchParams.has("url")) {
      try {
        const res = await callRubika("updateBotEndpoints", { url: url.searchParams.get("url"), type: "webhook" });
        return new Response(JSON.stringify({ ok: true, result: res }, null, 2), { headers: { "Content-Type": "application/json" } });
      } catch (e) {
        return new Response(JSON.stringify({ ok: false, error: e.message }, null, 2), { headers: { "Content-Type": "application/json" } });
      }
    }

    // 2. Admin Panel Route
    if (url.pathname === "/admin" || url.searchParams.has("admin")) {
      if (url.searchParams.get("pass") !== ADMIN_PANEL_PASS) {
        return new Response("<h1>401 Unauthorized</h1>", { status: 401, headers: { "Content-Type": "text/html; charset=utf-8" } });
      }
      return await renderAdminPanel(request);
    }

    // 3. Webhook Handler
    if (request.method === "POST") {
      try {
        const payload = await request.json();
        await handleWebhook(payload);
      } catch (e) {
        console.error("Webhook Error:", e);
      }
      return new Response("OK", { status: 200 });
    }

    return new Response("Regnar VIP Bot is running.", { status: 200 });
  }
};

// ═══════════════════════════════════════════════════════════
// Rubika API
// ═══════════════════════════════════════════════════════════
async function callRubika(method, payload = {}) {
  const res = await fetch(`https://botapi.rubika.ir/v3/${BOT_TOKEN}/${method}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });
  return res.json();
}

async function sendMessage(chatId, text, inlineKeypad = null) {
  const payload = { chat_id: chatId, text: text };
  if (inlineKeypad) payload.inline_keypad = inlineKeypad;
  return callRubika("sendMessage", payload);
}

async function getChatMember(chatId, userId) {
  return callRubika("getChatMember", { chat_id: chatId, user_id: userId });
}

// ═══════════════════════════════════════════════════════════
// Crypto (ترجمه دقیق از PHP)
// ═══════════════════════════════════════════════════════════
function rol8(x, n) { n %= 8; return ((x << n) | (x >> (8 - n))) & 0xFF; }
function ror8(x, n) { n %= 8; return ((x >> n) | (x << (8 - n))) & 0xFF; }

function customEncrypt(plain, key, seed) {
  const encoder = new TextEncoder();
  const bytes = encoder.encode(plain);
  const kl = key.length;
  let outStr = "";
  
  for (let i = 0; i < bytes.length; i++) {
    const idx = i + 1;
    const k = key.charCodeAt((idx + seed - 1) % kl);
    const r = (seed * 7 + idx * 13) % 8;
    let x = bytes[i] ^ k;
    x = rol8(x, r);
    x ^= (seed + idx * 31) % 256;
    outStr += String.fromCharCode(x);
  }
  return btoa(outStr);
}

function customDecrypt(b64, key, seed) {
  const rawStr = atob(b64);
  const kl = key.length;
  const bytes = new Uint8Array(rawStr.length);
  
  for (let i = 0; i < rawStr.length; i++) {
    const idx = i + 1;
    let x = rawStr.charCodeAt(i);
    x ^= (seed + idx * 31) % 256;
    const r = (seed * 7 + idx * 13) % 8;
    x = ror8(x, r);
    const k = key.charCodeAt((idx + seed - 1) % kl);
    x ^= k;
    bytes[i] = x;
  }
  const decoder = new TextDecoder();
  return decoder.decode(bytes);
}

// ═══════════════════════════════════════════════════════════
// GitHub Storage
// ═══════════════════════════════════════════════════════════
function ghHeaders() {
  return {
    "Authorization": `token ${GH_TOKEN}`,
    "Accept": "application/vnd.github.v3+json",
    "User-Agent": "RegnarVIP"
  };
}

async function ghGetFile(path) {
  const res = await fetch(`https://api.github.com/repos/${GH_OWNER}/${GH_REPO}/contents/${path}?ref=${GH_BRANCH}`, { headers: ghHeaders() });
  if (res.status === 404) return null;
  if (!res.ok) throw new Error(`GH GET ${res.status}`);
  const d = await res.json();
  return { sha: d.sha, content: d.content.replace(/[\n\r]/g, "") };
}

async function ghPutFile(path, contentB64, message) {
  const body = { message, content: contentB64, branch: GH_BRANCH };
  let m = await ghGetFile(path);
  if (m) body.sha = m.sha;
  
  let res = await fetch(`https://api.github.com/repos/${GH_OWNER}/${GH_REPO}/contents/${path}`, {
    method: "PUT",
    headers: { ...ghHeaders(), "Content-Type": "application/json" },
    body: JSON.stringify(body)
  });

  if (res.status === 409 || res.status === 422) {
    const f = await ghGetFile(path);
    if (f) {
      body.sha = f.sha;
      res = await fetch(`https://api.github.com/repos/${GH_OWNER}/${GH_REPO}/contents/${path}`, {
        method: "PUT",
        headers: { ...ghHeaders(), "Content-Type": "application/json" },
        body: JSON.stringify(body)
      });
    }
  }
  if (res.status !== 200 && res.status !== 201) throw new Error(`GH PUT ${res.status}`);
}

async function loadAccounts() {
  try {
    const m = await ghGetFile(GH_PATH);
    if (!m) return [];
    const env = JSON.parse(atob(m.content));
    return JSON.parse(customDecrypt(env.data, ENC_KEY, ENC_SEED)) || [];
  } catch (e) { console.error("load_acc:", e); return []; }
}

async function saveAccounts(accs) {
  const plain = JSON.stringify(accs);
  const enc = customEncrypt(plain, ENC_KEY, ENC_SEED);
  const env = JSON.stringify({ v: 1, data: enc });
  await ghPutFile(GH_PATH, btoa(env), `Regnar: accounts (${accs.length})`);
}

async function loadUsers() {
  try {
    const m = await ghGetFile(GH_USERS_PATH);
    if (!m) return [];
    const d = JSON.parse(atob(m.content));
    return Array.isArray(d) ? d : [];
  } catch (e) { return []; }
}

async function saveUsers(users) {
  const content = JSON.stringify([...new Set(users)]);
  await ghPutFile(GH_USERS_PATH, btoa(content), `Regnar: users (${users.length})`);
}

async function registerUser(chatId) {
  if (!chatId) return;
  try {
    const users = await loadUsers();
    if (!users.includes(String(chatId))) {
      users.push(String(chatId));
      await saveUsers(users);
    }
  } catch (e) { console.error("reg_user:", e); }
}

async function getAdminState(uid) {
    try {
        const m = await ghGetFile(GH_STATE_PATH);
        if (!m) return null;
        const states = JSON.parse(atob(m.content));
        return states[uid] || null;
    } catch { return null; }
}

async function setAdminState(uid, state) {
    try {
        const m = await ghGetFile(GH_STATE_PATH);
        let states = m ? JSON.parse(atob(m.content)) : {};
        if (state === null) delete states[uid];
        else states[uid] = state;
        await ghPutFile(GH_STATE_PATH, btoa(JSON.stringify(states)), `State update: ${uid}`);
    } catch (e) { console.error("State error", e); }
}

// ═══════════════════════════════════════════════════════════
// Logic Functions
// ═══════════════════════════════════════════════════════════
function genUsername() {
  const chars = "abcdefghijklmnopqrstuvwxyz0123456789";
  let s = "regnar_";
  for (let i = 0; i < 10; i++) s += chars[Math.floor(Math.random() * chars.length)];
  return s;
}

function genPassword() {
  const chars = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%&*";
  let s = "";
  for (let i = 0; i < 20; i++) s += chars[Math.floor(Math.random() * chars.length)];
  return s;
}

async function createPlan(uid, minutes = PLAN_DURATION_MIN) {
  const now = new Date();
  const exp = new Date(now.getTime() + minutes * 60000);
  
  const acc = {
    u: genUsername(),
    p: genPassword(),
    c: now.toISOString().split('T')[0],
    e: exp.toISOString().split('T')[0],
    uid: String(uid),
    created_at: now.toISOString(),
    expires_at: exp.toISOString(),
    exp_ts: Math.floor(exp.getTime() / 1000),
    duration_min: parseInt(minutes)
  };
  
  const accs = await loadAccounts();
  accs.push(acc);
  await saveAccounts(accs);
  return acc;
}

async function isUserMember(uid) {
  for (const chat of [CHANNEL_USERNAME, CHANNEL_ID]) {
    try {
      const r = await getChatMember(chat, uid);
      if (!r || r.status === "ERROR") continue;
      
      let memberStatus = null;
      if (r.data?.chat_member?.status) memberStatus = r.data.chat_member.status;
      else if (r.chat_member?.status) memberStatus = r.chat_member.status;
      
      if (memberStatus) {
        const s = String(memberStatus).toLowerCase();
        if (["member", "admin", "creator", "owner", "moderator"].includes(s)) return true;
      }
    } catch (e) { console.error(`gcm(${chat}):`, e); }
  }
  return false;
}

// ═══════════════════════════════════════════════════════════
// Keypads
// ═══════════════════════════════════════════════════════════
const mainKeypad = () => ({
  rows: [{ buttons: [{ id: BTN_GET_PLAN_ID, type: "Simple", button_text: BTN_GET_PLAN_TEXT }] }]
});

const joinKeypad = () => ({
  rows: [
    { buttons: [{ id: BTN_JOIN_CHANNEL_ID, type: "Link", button_text: BTN_JOIN_CHANNEL_TEXT, button_link: CHANNEL_LINK }] },
    { buttons: [{ id: BTN_CHECK_JOIN_ID, type: "Simple", button_text: BTN_CHECK_JOIN_TEXT }] },
    { buttons: [{ id: BTN_BACK_ID, type: "Simple", button_text: BTN_BACK_TEXT }] }
  ]
});

const adminKeypad = () => ({
  rows: [
    { buttons: [{ id: ADM_BROADCAST, type: "Simple", button_text: '📢 پیام همگانی' }, { id: ADM_CREATE, type: "Simple", button_text: '🔑 ساخت کلید' }] },
    { buttons: [{ id: ADM_RESET, type: "Simple", button_text: '♻️ ریست کاربر' }, { id: ADM_STATS, type: "Simple", button_text: '📊 آمار' }] },
    { buttons: [{ id: ADM_LIST, type: "Simple", button_text: '📋 لیست کلیدها' }] }
  ]
});

// ═══════════════════════════════════════════════════════════
// User & Admin Flows
// ═══════════════════════════════════════════════════════════
async function sendWelcome(chatId) {
  await sendMessage(chatId, "👋 سلام!\n\nبه ربات Regnar VIP خوش آمدید.\n\n🎁 برای دریافت پلن رایگان ۱ ساعته، دکمه زیر را لمس کنید.", mainKeypad());
}

async function sendJoinPrompt(chatId) {
  await sendMessage(chatId, `⚠️ برای دریافت پلن، ابتدا عضو کانال شوید:\n\n📢 ${CHANNEL_USERNAME}\n\n1️⃣ روی دکمه «عضویت در کانال» بزنید.\n2️⃣ سپس دکمه «عضو شدم، بررسی کن» را بزنید.`, joinKeypad());
}

async function flowGetPlan(chatId, userId) {
  if (!userId) return sendMessage(chatId, "⚠️ شناسه کاربر یافت نشد. لطفاً /start را بزنید.");
  if (!(await isUserMember(userId))) return sendJoinPrompt(chatId);

  const accs = await loadAccounts();
  const existing = accs.find(a => String(a.uid) === String(userId));

  if (existing) {
    const remaining = parseInt(existing.exp_ts || 0) - Math.floor(Date.now() / 1000);
    if (remaining > 0) {
      const min = Math.floor(remaining / 60);
      const sec = remaining % 60;
      return sendMessage(chatId, `⏳ شما قبلاً پلن رایگان گرفته‌اید.\n\n👤 Username:  ${existing.u}\n🔑 Password:  ${existing.p}\n\n⏱ باقی‌مانده: ${min} دقیقه و ${sec} ثانیه`, mainKeypad());
    }
    return sendMessage(chatId, `❌ پلن رایگان شما منقضی شده است.\n\n👤 Username:  ${existing.u}\n🔑 Password:  ${existing.p}\n\n⚠️ هر کاربر فقط یک بار می‌تواند پلن رایگان دریافت کند.`, mainKeypad());
  }

  try {
    const acc = await createPlan(userId);
    await sendMessage(chatId, `🎉 تبریک! پلن رایگان ۱ ساعته فعال شد.\n\n👤 Username:  ${acc.u}\n🔑 Password:  ${acc.p}\n\n⏰ این اطلاعات تا ۶۰ دقیقه دیگر معتبر است.\n⚠️ هر کاربر فقط یک بار می‌تواند پلن رایگان بگیرد.`, mainKeypad());
  } catch (e) {
    console.error("create_plan:", e);
    await sendMessage(chatId, "⚠️ خطا در ساخت پلن. چند لحظه بعد دوباره تلاش کنید.", mainKeypad());
  }
}

async function sendAdminPanel(chatId) {
  await sendMessage(chatId, "👑 پنل مدیریت Regnar VIP\n\nاز دکمه‌های زیر استفاده کنید:\n\n📢 پیام همگانی — ارسال به همه کاربران\n🔑 ساخت کلید — با مدت دلخواه (دقیقه)\n♻️ ریست کاربر — اجازه دریافت مجدد\n📊 آمار — وضعیت کلی\n📋 لیست کلیدها — همه کلیدها", adminKeypad());
}

async function handleAdminButton(chatId, userId, btnId) {
  if (String(userId) !== String(ADMIN_ID)) return false;

  switch (btnId) {
    case ADM_STATS: {
      const accs = await loadAccounts();
      const users = await loadUsers();
      const active = accs.filter(a => parseInt(a.exp_ts || 0) > Math.floor(Date.now() / 1000)).length;
      await sendMessage(chatId, `📊 آمار ربات\n\n👥 کاربران ذخیره‌شده: ${users.length}\n🔑 کل کلیدها: ${accs.length}\n✅ کلیدهای فعال: ${active}\n❌ کلیدهای منقضی: ${accs.length - active}`, adminKeypad());
      return true;
    }
    case ADM_LIST: {
      const accs = await loadAccounts();
      if (!accs.length) { await sendMessage(chatId, "📋 هیچ کلیدی وجود ندارد.", adminKeypad()); return true; }
      let msg = `📋 لیست کلیدها (${accs.length})\n\n`;
      let i = 0;
      for (const a of accs) {
        i++;
        const st = (parseInt(a.exp_ts || 0) > Math.floor(Date.now() / 1000)) ? "✅" : "❌";
        msg += `${i}. ${st} ${a.u}\n   UID: ${a.uid || "-"}\n   انقضا: ${a.expires_at || "-"}\n\n`;
        if (i >= 20) { msg += `... و ${accs.length - 20} مورد دیگر\n`; break; }
      }
      await sendMessage(chatId, msg, adminKeypad());
      return true;
    }
    case ADM_BROADCAST:
      await setAdminState(userId, { step: "broadcast" });
      await sendMessage(chatId, "📢 متن پیام همگانی را ارسال کنید:\n\n(برای لغو /cancel را بزنید)");
      return true;
    case ADM_CREATE:
      await setAdminState(userId, { step: "create_key" });
      await sendMessage(chatId, "🔑 مدت کلید جدید را به دقیقه بفرستید:\n\nمثال: 60 = یک ساعت، 720 = ۱۲ ساعت، 1440 = ۲۴ ساعت\nبرای لغو /cancel");
      return true;
    case ADM_RESET:
      await setAdminState(userId, { step: "reset_user" });
      await sendMessage(chatId, "♻️ شناسه کاربر (uid) را بفرستید تا کلیدش حذف شود و بتواند دوباره کلید بگیرد:\n\nبرای لغو /cancel");
      return true;
    case BTN_BACK_ID:
      await sendAdminPanel(chatId);
      return true;
  }
  return false;
}

async function handleAdminText(chatId, userId, text) {
  if (String(userId) !== String(ADMIN_ID)) return false;
  const state = await getAdminState(userId);

  if (text === "/admin" || text === "/panel" || text === "/cancel") {
    await setAdminState(userId, null);
    await sendAdminPanel(chatId);
    return true;
  }

  if (text.startsWith("/createkey ")) {
    const match = text.match(/^\/createkey\s+(\d+)(?:\s+(\S+))?/);
    if (match) {
      const minutes = Math.max(1, parseInt(match[1]));
      const uid = match[2] || `admin_${Math.floor(Date.now() / 1000)}_${Math.floor(Math.random() * 900) + 100}`;
      try {
        const acc = await createPlan(uid, minutes);
        await sendMessage(chatId, `✅ کلید ساخته شد.\n\n👤 Username:  ${acc.u}\n🔑 Password:  ${acc.p}\n🆔 UID:  ${acc.uid}\n⏱ مدت:  ${minutes} دقیقه\n⏰ انقضا:  ${acc.expires_at}`, adminKeypad());
      } catch (e) { await sendMessage(chatId, "❌ خطا: " + e.message, adminKeypad()); }
      return true;
    }
  }

  if (text.startsWith("/reset ")) {
    const target = text.substring(7).trim();
    const accs = await loadAccounts();
    const newAccs = accs.filter(a => String(a.uid) !== target);
    if (newAccs.length === accs.length) {
      await sendMessage(chatId, "❌ کاربری با این UID پیدا نشد.", adminKeypad());
    } else {
      await saveAccounts(newAccs);
      await sendMessage(chatId, `✅ کلید کاربر ${target} حذف شد. الان می‌تواند دوباره بگیرد.`, adminKeypad());
    }
    return true;
  }

  if (text.startsWith("/broadcast ")) {
    const msg = text.substring(11);
    if (msg) { await broadcastMessage(chatId, msg); return true; }
  }

  if (!state) return false;

  if (state.step === "broadcast") {
    await setAdminState(userId, null);
    await broadcastMessage(chatId, text);
    return true;
  }

  if (state.step === "create_key") {
    const match = text.match(/^(\d+)(?:\s+(\S+))?$/);
    if (!match) {
      await sendMessage(chatId, "⚠️ فرمت اشتباه. فقط عدد دقیقه بفرستید.\nمثال: 60");
      return true;
    }
    const minutes = Math.max(1, parseInt(match[1]));
    const uid = match[2] || `admin_${Math.floor(Date.now() / 1000)}_${Math.floor(Math.random() * 900) + 100}`;
    await setAdminState(userId, null);
    try {
      const acc = await createPlan(uid, minutes);
      await sendMessage(chatId, `✅ کلید ساخته شد.\n\n👤 Username:  ${acc.u}\n🔑 Password:  ${acc.p}\n🆔 UID:  ${acc.uid}\n⏱ مدت:  ${minutes} دقیقه\n⏰ انقضا:  ${acc.expires_at}`, adminKeypad());
    } catch (e) { await sendMessage(chatId, "❌ خطا: " + e.message, adminKeypad()); }
    return true;
  }

  if (state.step === "reset_user") {
    await setAdminState(userId, null);
    const target = text.trim();
    const accs = await loadAccounts();
    const newAccs = accs.filter(a => String(a.uid) !== target);
    if (newAccs.length === accs.length) {
      await sendMessage(chatId, "❌ کاربری با این UID پیدا نشد.", adminKeypad());
    } else {
      await saveAccounts(newAccs);
      await sendMessage(chatId, `✅ ریست شد. کاربر ${target} الان می‌تواند دوباره کلید بگیرد.`, adminKeypad());
    }
    return true;
  }
  return false;
}

async function broadcastMessage(adminChatId, text) {
  const users = await loadUsers();
  let ok = 0, fail = 0;
  for (const cId of users) {
    try {
      await sendMessage(cId, `📢 اطلاعیه:\n\n${text}`);
      ok++;
      await new Promise(r => setTimeout(r, 80));
    } catch { fail++; }
  }
  await sendMessage(adminChatId, `✅ ارسال شد.\n\n✅ موفق: ${ok}\n❌ ناموفق: ${fail}`, adminKeypad());
}

// ═══════════════════════════════════════════════════════════
// Webhook Update Handler
// ═══════════════════════════════════════════════════════════
async function handleWebhook(payload) {
  let chatId = null, userId = null, text = "", buttonId = null;

  if (payload.update?.new_message) {
    const msg = payload.update.new_message;
    chatId = String(payload.update.chat_id || "");
    userId = String(msg.sender_id || payload.update.sender_id || "");
    text = String(msg.text || msg.body || "").trim();
    if (msg.aux_data?.button_id) buttonId = String(msg.aux_data.button_id);
  } else if (payload.inline_message) {
    const inline = payload.inline_message;
    chatId = String(inline.chat_id || "");
    userId = String(inline.sender_id || "");
    if (inline.aux_data?.button_id) buttonId = String(inline.aux_data.button_id);
  }

  if (chatId) await registerUser(chatId);

  if (buttonId && String(userId) === String(ADMIN_ID)) {
    if (await handleAdminButton(chatId, userId, buttonId)) return;
  }

  if (buttonId) {
    if ([BTN_GET_PLAN_ID, BTN_CHECK_JOIN_ID].includes(buttonId)) {
      await flowGetPlan(chatId, userId);
    } else if (buttonId === BTN_BACK_ID) {
      await sendWelcome(chatId);
    }
    return;
  }

  if (String(userId) === String(ADMIN_ID) && text !== "") {
    if (await handleAdminText(chatId, userId, text)) return;
  }

  if (text === "" || ["/start", "start", "شروع"].includes(text)) {
    await sendWelcome(chatId);
  } else if ([BTN_GET_PLAN_TEXT, BTN_CHECK_JOIN_TEXT].includes(text)) {
    await flowGetPlan(chatId, userId);
  } else if (text === BTN_BACK_TEXT) {
    await sendWelcome(chatId);
  } else {
    await sendWelcome(chatId);
  }
}

// ═══════════════════════════════════════════════════════════
// HTML Admin Panel
// ═══════════════════════════════════════════════════════════
async function renderAdminPanel(request) {
  let flashMsg = "";
  let flashCls = "ok";

  if (request.method === "POST") {
    try {
      const formData = await request.formData();
      const action = formData.get("action");
      
      if (action === "create_key") {
        const minutes = Math.max(1, parseInt(formData.get("minutes") || 60));
        let uid = String(formData.get("uid") || "").trim();
        if (!uid) uid = `admin_${Math.floor(Date.now()/1000)}_${Math.floor(Math.random()*900)+100}`;
        const acc = await createPlan(uid, minutes);
        flashMsg = `✅ کلید ساخته شد — Username: ${acc.u} / Password: ${acc.p} / UID: ${acc.uid} / ${minutes} دقیقه`;
      } else if (action === "delete") {
        const u = formData.get("u");
        const accs = await loadAccounts();
        await saveAccounts(accs.filter(a => a.u !== u));
        flashMsg = `🗑 کلید ${u} حذف شد.`;
      } else if (action === "broadcast") {
        const msg = String(formData.get("message") || "").trim();
        if (msg) {
          const users = await loadUsers();
          let ok = 0, fail = 0;
          for (const cId of users) {
            try { await sendMessage(cId, `📢 اطلاعیه:\n\n${msg}`); ok++; await new Promise(r=>setTimeout(r,80)); }
            catch { fail++; }
          }
          flashMsg = `📤 ارسال شد — موفق: ${ok} / ناموفق: ${fail}`;
        }
      }
    } catch (e) {
      flashCls = "err";
      flashMsg = "❌ خطا: " + e.message;
    }
  }

  const accounts = await loadAccounts();
  const users = await loadUsers();
  const nowTs = Math.floor(Date.now() / 1000);
  const active = accounts.filter(a => parseInt(a.exp_ts || 0) > nowTs).length;
  const total = accounts.length;
  const expired = total - active;

  let rows = "";
  if (!accounts.length) {
    rows = "<tr><td colspan='6' style='text-align:center;color:#666;padding:30px'>هیچ کلیدی وجود ندارد</td></tr>";
  } else {
    for (const a of accounts.reverse()) {
      const st = parseInt(a.exp_ts || 0) > nowTs ? "<span class='tag ok'>فعال</span>" : "<span class='tag exp'>منقضی</span>";
      const h_u = String(a.u || "-").replace(/'/g, "\\'");
      rows += `<tr>
        <td><code>${a.u || "-"}</code></td>
        <td><code>${a.p || "-"}</code></td>
        <td>${a.uid || "-"}</td>
        <td>${a.expires_at || "-"}</td>
        <td>${st}</td>
        <td>
          <form method='POST' style='display:inline' onsubmit='return confirm("حذف شود؟")'>
          <input type='hidden' name='action' value='delete'>
          <input type='hidden' name='u' value='${h_u}'>
          <button type='submit' class='danger small'>حذف</button>
          </form>
        </td>
      </tr>`;
    }
  }

  const flash = flashMsg ? `<div class='msg ${flashCls}'>${flashMsg}</div>` : "";
  const pass = new URL(request.url).searchParams.get("pass");

  const html = `
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Regnar VIP — Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma,sans-serif}
body{background:#0b0f14;color:#e6e8eb;padding:20px;line-height:1.6}
.container{max-width:1100px;margin:0 auto}
h1{font-size:26px;margin-bottom:8px;background:linear-gradient(90deg,#7ac8ff,#fff);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;font-weight:800;letter-spacing:.05em}
h2{font-size:18px;color:#7ac8ff;margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid #1e2631}
.sub{color:#888;font-size:13px;margin-bottom:20px}
section{background:#141a22;border:1px solid #1e2631;border-radius:12px;padding:18px;margin-bottom:16px}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px}
.stat{background:#0e1319;padding:14px;border-radius:10px;border:1px solid #1e2631}
.stat .n{font-size:24px;font-weight:800;color:#7ac8ff}
.stat .l{font-size:12px;color:#888;margin-top:4px}
input,textarea,select{width:100%;padding:10px 12px;background:#0e1319;border:1px solid #283040;color:#e6e8eb;border-radius:8px;font-family:inherit;font-size:14px;margin-bottom:10px;outline:none}
input:focus,textarea:focus{border-color:#7ac8ff}
textarea{resize:vertical;min-height:100px}
label{display:block;font-size:12px;color:#888;margin-bottom:4px}
button{padding:10px 20px;background:linear-gradient(135deg,#7ac8ff,#4a9ed8);color:#0b0f14;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;transition:filter .15s}
button:hover{filter:brightness(1.15)}
button.danger{background:linear-gradient(135deg,#ff5a6e,#c23a4c);color:#fff}
button.small{padding:5px 10px;font-size:12px}
table{width:100%;border-collapse:collapse;font-size:13px}
th,td{padding:10px;text-align:right;border-bottom:1px solid #1e2631}
th{color:#7ac8ff;font-size:12px;text-transform:uppercase;letter-spacing:.05em}
tr:hover{background:#0e1319}
.tag{display:inline-block;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:700}
.tag.ok{background:#1a3d29;color:#5cff9e}
.tag.exp{background:#3d1a1a;color:#ff6b6b}
.msg{padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px}
.msg.ok{background:#1a3d29;color:#5cff9e;border:1px solid #2a5a3f}
.msg.err{background:#3d1a1a;color:#ff6b6b;border:1px solid #5a2a2a}
code{background:#0e1319;padding:2px 6px;border-radius:4px;font-family:monospace;font-size:12px;color:#7ac8ff}
.flex{display:flex;gap:10px;flex-wrap:wrap}
.flex > *{flex:1;min-width:150px}
</style>
</head>
<body>
<div class="container">
<h1>👑 پنل مدیریت Regnar VIP</h1>
<p class="sub">ساخت، حذف و مدیریت کلیدها + ارسال پیام همگانی</p>
${flash}
<section>
<h2>📊 آمار</h2>
<div class="stats">
<div class="stat"><div class="n">${total}</div><div class="l">کل کلیدها</div></div>
<div class="stat"><div class="n">${active}</div><div class="l">کلیدهای فعال</div></div>
<div class="stat"><div class="n">${expired}</div><div class="l">کلیدهای منقضی</div></div>
<div class="stat"><div class="n">${users.length}</div><div class="l">کاربران ذخیره‌شده</div></div>
</div>
</section>
<section>
<h2>🔑 ساخت کلید جدید</h2>
<form method="POST">
<input type="hidden" name="action" value="create_key">
<div class="flex">
<div><label>مدت (دقیقه)</label><input type="number" name="minutes" value="60" min="1" required></div>
<div><label>UID (اختیاری)</label><input type="text" name="uid" placeholder="خالی = خودکار"></div>
</div>
<button type="submit">🔨 ساخت</button>
</form>
</section>
<section>
<h2>📢 ارسال پیام همگانی</h2>
<form method="POST">
<input type="hidden" name="action" value="broadcast">
<label>متن پیام</label>
<textarea name="message" placeholder="متن پیام شما..." required></textarea>
<button type="submit">📤 ارسال به همه</button>
</form>
</section>
<section>
<h2>📋 لیست کلیدها (${total})</h2>
<table>
<thead><tr><th>Username</th><th>Password</th><th>UID</th><th>انقضا</th><th>وضعیت</th><th>عملیات</th></tr></thead>
<tbody>${rows}</tbody>
</table>
</section>
</div>
</body>
</html>`;

  return new Response(html, { headers: { "Content-Type": "text/html; charset=utf-8" } });
}