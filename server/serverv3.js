// serverv3.js — OpenRouter "BULLETPROOF" Capstone Edition
// Features: 30-Model Ultimate Fallback System, Auto-Switching, Full Medical Logic
// Status: Ready for Defense (Dec 2025 Optimized & Fixed Treatment Logic)

import express from "express";
import cors from "cors";
import dotenv from "dotenv";
import OpenAI from "openai";
import path from "path";
import { fileURLToPath } from "url";

// ==========================================================
// 0. CONFIG & AUTH
// ==========================================================
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
dotenv.config({ path: path.join(__dirname, ".env"), override: true });

const app = express();
app.use(cors());
app.use(express.json());

// ✅ API KEY (from .env / Render env)
const OPENROUTER_API_KEY = process.env.OPENROUTER_API_KEY;

// ✅ ULTIMATE 30-MODEL DEFENSE LIST
const FREE_MODELS = [
  // --- TIER 1: PROVEN FAST & RELIABLE (The "First Responders") ---
  "mistralai/devstral-2512:free",                 // Proven working
  "nvidia/nemotron-3-nano-30b-a3b:free",          // Proven working, very logical
  "meta-llama/llama-3.2-3b-instruct:free",        // Super fast, lightweight
  "meta-llama/llama-3.1-8b-instruct:free",        // Standard stable model
  "huggingfaceh4/zephyr-7b-beta:free",            // Reliable classic

  // --- TIER 2: HEAVY HITTERS (Smartest Models) ---
  "google/gemini-2.0-flash-exp:free",             // Best reasoning
  "meta-llama/llama-3.3-70b-instruct:free",       // Powerful 70B model
  "nousresearch/hermes-3-llama-3.1-405b:free",    // Massive 405B model
  "google/gemma-3-27b-it:free",                   // Google's latest open source
  "qwen/qwen-2.5-coder-32b-instruct:free",        // Strong logic & reasoning

  // --- TIER 3: NEW & EXPERIMENTAL (Features) ---
  "google/gemma-3-12b-it:free",                   // Multimodal capable
  "alibaba/tongyi-deepresearch-30b-a3b:free",     // Deep research capability
  "amazon/nova-2-lite-v1:free",                   // Amazon's lightweight model
  "nex-agi/deepseek-v3.1-nex-n1:free",            // DeepSeek variant
  "google/gemini-2.0-flash-lite-preview-02-05:free", // Lightweight Gemini

  // --- TIER 4: SPECIALIZED & NICHE ---
  "arcee-ai/trinity-mini:free",
  "tngtech/tng-r1t-chimera:free",
  "allenai/olmo-3-32b-think:free",
  "mistralai/mistral-7b-instruct:free",           // Older stable Mistral
  "openai/gpt-oss-120b:free",                     // OpenAI OSS

  // --- TIER 5: DEEP BACKUPS (Just in case) ---
  "meta-llama/llama-3.2-1b-instruct:free",        // Ultra-lightweight fallback
  "google/gemini-2.0-flash-thinking-exp:free",    // Thinking model
  "google/gemini-exp-1206:free",                  // Older Gemini experimental
  "microsoft/phi-3-mini-128k-instruct:free",      // Microsoft Phi
  "qwen/qwen-2.5-72b-instruct:free",              // Big Qwen
  "deepseek/deepseek-r1-distill-llama-70b:free",  // DeepSeek R1
  "sophosympatheia/midnight-rose-70b:free",       // Creative/RP model
  "gryphe/mythomax-l2-13b:free",                  // Stable roleplay model
  "openchat/openchat-7b:free",                    // OpenChat
  "undi95/toppy-m-7b:free"                        // Toppy M
];

console.log("------------------------------------------------");
if (!OPENROUTER_API_KEY) {
  console.error("❌ ERROR: Missing OPENROUTER_API_KEY");
  process.exit(1);
} else {
  console.log("🔑 API Key Loaded");
  console.log("🛡️ Capstone Defense Mode: ACTIVE");
  console.log("📋 Fallback Models Loaded:", FREE_MODELS.length, "models ready.");
}
console.log("------------------------------------------------");

const client = new OpenAI({
  baseURL: "https://openrouter.ai/api/v1",
  apiKey: OPENROUTER_API_KEY,
  defaultHeaders: {
    "HTTP-Referer": "http://localhost:5000", // update to prod URL sa Render kung gusto mo
    "X-Title": "GabayAI Analyzer V3 Capstone",
  },
});

// ==========================================================
// 1. LANGUAGE DETECTION (Tagalog / English)
// ==========================================================
function detectLanguage(text) {
  if (!text) return "en";
  const lower = text.toLowerCase();
  const tokens = lower.replace(/[^\w\s]/g, " ").split(/\s+/).filter(Boolean);

  const tlMarkers = [
    "ang","ng","sa","na","yung","mga","ay","at","o","ni","kay",
    "ba","po","opo","kasi","naman","lang","din","rin","tsaka",
    "pero","dahil","kung","kapag","pag","bago","tapos","kaya",
    "ko","mo","ka","ako","ikaw","siya","niya","namin","atin","inyo",
    "ano","saan","kailan","bakit","paano","gaano","sino",
    "masakit","sakit","sumasakit","kumikirot","mahapdi","hapdi",
    "kati","makati","namamaga","maga","mainit","lagnat","sinat",
    "ulo","tiyan","tyan","likod","binti","paa","kamay","balat",
    "ubo","sipon","hilo","suka","tae","dumi","ihi","dugo",
    "nararamdaman","hirap","hinga",
    "ngayon","kahapon","kanina","bukas","araw","linggo","buwan"
  ];

  let score = 0;
  tokens.forEach(t => { if (tlMarkers.includes(t)) score++; });
  return score >= 2 ? "tl" : "en";
}

// ==========================================================
// 2. FALLBACK QUESTIONS
// ==========================================================
function fallbackQuestionsEn() {
  return {
    questions: [
      { question: "How long have you been feeling this?", options: ["Since today", "2-7 days", "More than a week", "I don't know"] },
      { question: "How painful is it (1-10)?", options: ["Mild (1-3)", "Moderate (4-6)", "Severe (7-10)", "I don't know"] },
      { question: "Does anything make it worse?", options: ["Movement", "Touching it", "Nothing", "I don't know"] },
    ],
  };
}

function fallbackQuestionsTl() {
  return {
    questions: [
      { question: "Gaano katagal mo na itong nararamdaman?", options: ["Ngayon lang", "2-7 araw na", "Mahigit isang linggo na", "Hindi ko sigurado"] },
      { question: "Gaano ito kasakit (1-10)?", options: ["Mild (1-3)", "Katamtaman (4-6)", "Sobrang sakit (7-10)", "Hindi ko sigurado"] },
      { question: "May nagpapalala ba sa nararamdaman mo?", options: ["Paggalaw", "Kapag hinahawakan", "Wala naman", "Hindi ko sigurado"] },
    ],
  };
}

// ==========================================================
// 3. ROBUST JSON PARSE (UPDATED FOR FIRST AID SUPPORT)
// ==========================================================
function safeJsonParse(raw, numericStep, lang) {
  const disclaimerEn = "Reminder: This is an AI tool only. Consult a doctor.";
  const disclaimerTl = "Paalala: AI tool lang ito. Kumunsulta sa doktor.";

  const buildFallback = () => {
    // A. Step 1 (Questions)
    if (numericStep === 1) return lang === "tl" ? fallbackQuestionsTl() : fallbackQuestionsEn();

    // B. First Aid Request (numericStep === 99)
    if (numericStep === 99) {
      return {
        title: "Advice Unavailable",
        subtext: "Please consult a doctor",
        overview: "We could not generate specific advice at this moment.",
        category: "General",
        icon: "fa-user-doctor",
        colorClass: "icon-blue",
        actions: [
          "Go to the nearest clinic or hospital if urgent.",
          "Call emergency services if life-threatening."
        ],
        warnings: ["Do not rely solely on AI for emergencies."],
        redFlags: "Severe pain, loss of consciousness, heavy bleeding."
      };
    }

    // C. Default: Step 2 (Analysis)
    return {
      urgency_level: "Urgent Care",
      triage_message: lang === "tl"
        ? "Hindi maproseso ang resulta. Magpakonsulta."
        : "Result processing failed. Consult a doctor.",
      drug_interaction_warning: "None",
      red_flags: [
        lang === "tl" ? "Technical error." : "Technical error.",
        lang === "tl" ? disclaimerTl : disclaimerEn
      ],
      conditions: [{
        name: "General Assessment Needed",
        overview: lang === "tl" ? "Kailangan ng check-up." : "Check-up needed.",
        symptoms_matched: ["Unknown"],
        treatment: ["Consult a doctor."],
        match_level: "Low",
        match_color: "#fbbf24",
      }],
    };
  };

  if (!raw) return buildFallback();

  let str = typeof raw === "string" ? raw.trim() : JSON.stringify(raw);
  str = str.replace(/``````/g, "").trim();

  try {
    const parsed = JSON.parse(str);
    return parsed;
  } catch (e) {
    try {
      const start = str.indexOf("{");
      const end = str.lastIndexOf("}");
      if (start !== -1 && end !== -1 && end > start) {
        return JSON.parse(str.substring(start, end + 1));
      }
    } catch (_) {}
  }

  return buildFallback();
}

// ==========================================================
// 4. CALL MODEL WITH LOOP (AUTO-SWITCHING LOGIC)
// ==========================================================
async function callModelWithLoop(payload, numericStep, lang) {
  for (const modelId of FREE_MODELS) {
    try {
      console.log(`📡 Trying Model: ${modelId}...`);
      payload.model = modelId;

      const completion = await client.chat.completions.create(payload);
      const reply = completion.choices[0]?.message?.content || "{}";

      if (!reply || reply.length < 5) throw new Error("Empty reply received");

      console.log(`✅ Success with ${modelId}!`);
      console.log("🤖 Reply snippet:", reply.slice(0, 60).replace(/\n/g, " ") + "...");

      return safeJsonParse(reply, numericStep, lang);
    } catch (error) {
      const status = error.status || "Unknown";
      console.warn(`⚠️ Failed ${modelId} (Status: ${status}): ${error.message}`);
    }
  }

  console.error("❌ ALL 30 MODELS FAILED. Returning fallback.");
  return safeJsonParse("{}", numericStep, lang);
}

// ==========================================================
// 5. MAIN ROUTE (/analyze)
// ==========================================================
app.post("/analyze", async (req, res) => {
  const { step, age, gender, height, weight, bloodPressure, symptoms, answers } = req.body;

  if (!symptoms || symptoms.trim() === "") {
    return res.status(400).json({ error: "Symptoms are required" });
  }

  const numericStep = Number(step) || 2;
  const lang = detectLanguage(symptoms);
  console.log(`\n📥 Input: "${symptoms.slice(0, 40)}..." | Lang: ${lang.toUpperCase()} | Step: ${numericStep}`);

  try {
    let prompt = "";

    // STEP 1 – QUESTIONS
    if (numericStep === 1) {
      prompt = (lang === "tl" ? `
You are a medical AI assistant. User Language: TAGALOG.
Goal: Generate 3 follow-up questions for: "${symptoms}" (Age: ${age}, Sex: ${gender}).
Format: { "questions": [ { "question": "...", "options": ["...", "Hindi ko alam"] } ] }
IMPORTANT: Return ONLY valid JSON. No markdown code blocks.
      ` : `
You are a medical AI assistant. User Language: ENGLISH.
Goal: Generate 3 follow-up questions for: "${symptoms}" (Age: ${age}, Sex: ${gender}).
Format: { "questions": [ { "question": "...", "options": ["...", "I don't know"] } ] }
IMPORTANT: Return ONLY valid JSON. No markdown code blocks.
      `).trim();
    }

    // STEP 2 – DIAGNOSIS
    else {
      const history = (answers || [])
        .map(a => `Q: ${a.question}\nA: ${a.answer}`)
        .join("\n");

      prompt = (lang === "tl" ? `
You are an expert medical AI (GabayAI). 
User Language: TAGALOG (simple, conversational Tagalog).
Return ONLY valid JSON. No markdown.

/* CORE RULES */
INPUT DATA: User: ${age}, ${gender}. Symptoms: "${symptoms}" History: ${history}

TASK 1 – DIAGNOSIS:
- Give TOP 5 POSSIBLE CONDITIONS.
- If vague: "Viral Upper Respiratory Infection", "Common Cold", "Indigestion".

TASK 2 – TRIAGE RULES:
- "Self Care": Mild flu-like, <=2 days.
- "Urgent Care": Moderate pain, >3 days fever.
- "Emergency": Trouble breathing, severe pain, fainting, bleeding.

TASK 3 – SEVERE PATTERN HINTS:
- Chest pain + trouble breathing -> "Acute Coronary Syndrome".
- One-sided weakness -> "Stroke".

TASK 4 – TREATMENT (CRITICAL FIX):
- Kung "Emergency": FIRST AID LANG. (e.g. "Manatiling kalmado", "Tumawag ng tulong").
- Kung "Self Care": 5 home remedies (Pahinga, Hydration).

TASK 5 – JSON OUTPUT (STRICT):
{
  "urgency_level": "Emergency" | "Urgent Care" | "Self Care",
  "triage_message": "Tagalog explanation.",
  "drug_interaction_warning": "Warning or None.",
  "red_flags": ["Warning 1"],
  "conditions": [
    {
      "name": "Condition Name",
      "overview": "Tagalog explanation.",
      "symptoms_matched": ["Symp 1", "Symp 2"],
      "treatment": ["Step 1", "Step 2"],
      "match_level": "High" | "Medium",
      "match_color": "#ef4444" | "#f59e0b"
    }
  ]
}
      ` : `
You are an expert medical AI. User Language: ENGLISH.
Return ONLY valid JSON. No markdown.

/* CORE RULES */
INPUT DATA: User: ${age}, ${gender}. Symptoms: "${symptoms}" History: ${history}

TASK 1 – DIAGNOSIS:
- Give TOP 5 POSSIBLE CONDITIONS.
- If vague: "Viral Upper Respiratory Infection", "Common Cold", "Indigestion".

TASK 2 – TRIAGE RULES:
- "Self Care": Mild symptoms.
- "Urgent Care": Moderate symptoms.
- "Emergency": Trouble breathing, severe pain, fainting.

TASK 3 – SEVERE PATTERN HINTS:
- Chest pain + breathlessness -> "Acute Coronary Syndrome".
- One-sided weakness -> "Stroke".

TASK 4 – TREATMENT (CRITICAL FIX):
- If "Emergency": FIRST AID ONLY. (e.g. "Stay calm", "Wait for ambulance").
- If "Self Care": 5 home remedies (Rest, Hydration).

TASK 5 – JSON OUTPUT (STRICT):
{
  "urgency_level": "Emergency" | "Urgent Care" | "Self Care",
  "triage_message": "Explanation.",
  "drug_interaction_warning": "Warning or None.",
  "red_flags": ["Warning 1"],
  "conditions": [
    {
      "name": "Condition Name",
      "overview": "Explanation.",
      "symptoms_matched": ["Symp 1"],
      "treatment": ["Step 1", "Step 2"],
      "match_level": "High" | "Medium",
      "match_color": "#ef4444" | "#f59e0b"
    }
  ]
}
      `).trim();
    }

    const payload = {
      messages: [
        { role: "system", content: "You are a medical API. Output STRICT JSON." },
        { role: "user", content: prompt },
      ],
      temperature: 0.1,
      max_tokens: 2000,
    };

    const parsed = await callModelWithLoop(payload, numericStep, lang);
    res.json(parsed);
  } catch (err) {
    console.error("❌ Server Error:", err.message);
    res.status(500).json({ error: "Internal Server Error" });
  }
});

// ==========================================================
// 6. NEW ROUTE: FIRST AID AI GENERATOR (/first-aid)
// ==========================================================
app.post("/first-aid", async (req, res) => {
  const { query } = req.body;

  if (!query) return res.status(400).json({ error: "Query required" });

  console.log(`🚑 Generating DETAILED First Aid for: "${query}"`);

  const prompt = `
You are an expert Medical First Aid AI.
Task: Provide a COMPREHENSIVE and DETAILED first aid guide for: "${query}".
Language: English (Professional but easy to understand).

IMPORTANT FORMATTING RULE:
- Use HTML <strong> tags to HIGHLIGHT key actions, medicines, or warnings.
- Example: "<strong>Apply direct pressure</strong> to the wound."

Structure Requirements:
- "overview": Provide a 2-sentence medical summary of what the condition is. (Highlight the condition name with <strong>).
- "actions": Provide 5-7 detailed steps. Each step should explain 'HOW' and 'WHY'. Use <strong> for the main verb/action.
- "warnings": List 3 critical things to AVOID. Use <strong> for "Do NOT".
- "redFlags": List specific signs that require 911/Emergency transport immediately. Use <strong> for the symptoms.

Output JSON format ONLY:
{
  "title": "Medical Name of Condition",
  "subtext": "Brief main symptoms (e.g. 'Heavy bleeding, pain')",
  "overview": "Detailed summary... <strong>Condition Name</strong> is...",
  "category": "Emergency" or "Home Care" or "First Aid",
  "icon": "fa-kit-medical",
  "colorClass": "icon-red" (if dangerous) or "icon-blue" (if mild) or "icon-green" (if safe),
  "actions": [
    "Step 1: <strong>Wash the wound</strong> with soap and water...",
    "Step 2: <strong>Apply pressure</strong> if bleeding continues...",
    "Step 3: Detail..."
  ],
  "warnings": ["<strong>Do NOT</strong> do X because...", "Avoid <strong>Y</strong>..."],
  "redFlags": "Go to hospital if: <strong>Symptom A</strong>, <strong>Symptom B</strong> occurs."
}
`;

  const payload = {
    messages: [
      { role: "system", content: "You are a First Aid API. Output STRICT JSON with HTML <strong> tags." },
      { role: "user", content: prompt },
    ],
    temperature: 0.2,
    max_tokens: 1200,
  };

  try {
    const result = await callModelWithLoop(payload, 99, "en");
    res.json(result);
  } catch (err) {
    console.error("First Aid Error:", err);
    res.status(500).json({ error: "Failed to generate tips" });
  }
});

// ==========================================================
// 7. START SERVER
// ==========================================================
const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
  console.log(`\n✅ GabayAI v3 (OpenRouter FREE) running on http://localhost:${PORT}`);
  console.log("📡 Models: 30-Model Ultimate Defense System Active");
  console.log("🛡️ Priority: Mistral > Nvidia > Llama > Gemini > Experimental");
  console.log("🌐 Languages: Tagalog & English\n");
});
