import express from "express";
import cors from "cors";
import dotenv from "dotenv";
import OpenAI from "openai";

dotenv.config();

const app = express();
app.use(cors());
app.use(express.json());

// ================================
// 0. CHECK API KEY
// ================================
if (!process.env.OPENAI_API_KEY) {
  console.error("❌ Missing OPENAI_API_KEY in .env");
  process.exit(1);
}

const client = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY,
});

// ==========================================================
// 1. IMPROVED LANGUAGE DETECTION (Tagalog / English)
// ==========================================================
function detectLanguage(text) {
  if (!text) return "en";
  const lower = text.toLowerCase();

  // Hiwalayin words, tanggal punctuation
  const tokens = lower.replace(/[^\w\s]/g, " ").split(/\s+/).filter(Boolean);

  const tlMarkers = [
    // Particles / Connectors
    "ang", "ng", "sa", "na", "yung", "mga", "ay", "at", "o", "ni", "kay",
    "ba", "po", "opo", "kasi", "naman", "lang", "din", "rin", "tsaka",
    "pero", "dahil", "kung", "kapag", "pag", "bago", "tapos", "kaya", "nyan", "nyon",

    // Pronouns
    "ko", "mo", "ka", "ako", "ikaw", "siya", "niya", "namin", "atin", "inyo", "sila", "kayo",

    // Common Questions
    "ano", "saan", "kailan", "bakit", "paano", "gaano", "sino", "meron", "wala",

    // Symptoms / Body / Feeling
    "masakit", "sakit", "sumasakit", "kumikirot", "mahapdi", "hapdi",
    "kati", "makati", "namamaga", "maga", "mainit", "lagnat", "sinat",
    "ulo", "tiyan", "tyan", "likod", "binti", "paa", "kamay", "balat", "dibdib", "lalamunan",
    "ubo", "sipon", "hilo", "suka", "tae", "dumi", "ihi", "dugo", "sugat",
    "pakiramdam", "nararamdaman", "hirap", "hinga",

    // Time
    "ngayon", "kahapon", "kanina", "bukas", "araw", "linggo", "buwan", "taon", "kagabi",

    // Common Words
    "sobra", "medyo", "parang", "grabe", "bigla", "matagal", "mabilis", "gamot", "doktor"
  ];

  let score = 0;
  tokens.forEach(t => {
    if (tlMarkers.includes(t)) score++;
  });

  return score >= 2 ? "tl" : "en";
}

// ==========================================================
// 2. FALLBACK QUESTIONS
// ==========================================================
function fallbackQuestionsEn() {
  return {
    questions: [
      {
        question: "How long have you been feeling this?",
        options: ["Since today", "2-7 days", "More than a week", "I don't know"]
      },
      {
        question: "How painful is it (1-10)?",
        options: ["Mild (1-3)", "Moderate (4-6)", "Severe (7-10)", "I don't know"]
      },
      {
        question: "Does anything make it worse?",
        options: ["Movement", "Touching it", "Nothing", "I don't know"]
      }
    ]
  };
}

function fallbackQuestionsTl() {
  return {
    questions: [
      {
        question: "Gaano katagal mo na itong nararamdaman?",
        options: ["Ngayon lang", "2-7 araw na", "Mahigit isang linggo na", "Hindi ko sigurado"]
      },
      {
        question: "Gaano ito kasakit (1-10)?",
        options: ["Mild (1-3)", "Katamtaman (4-6)", "Sobrang sakit (7-10)", "Hindi ko sigurado"]
      },
      {
        question: "May nagpapalala ba sa nararamdaman mo?",
        options: ["Paggalaw", "Kapag hinahawakan", "Wala naman", "Hindi ko sigurado"]
      }
    ]
  };
}

// ==========================================================
// 3. SAFE JSON PARSE (with partial recovery)
// ==========================================================
function safeJsonParse(raw, numericStep, lang) {
  const disclaimerEn = "Reminder: This is an AI tool only. Consult a doctor.";
  const disclaimerTl = "Paalala: AI tool lang ito. Kumunsulta sa doktor.";

  const buildFallback = () => {
    if (numericStep === 1) {
      return lang === "tl" ? fallbackQuestionsTl() : fallbackQuestionsEn();
    } else {
      return {
        urgency_level: "Self Care",
        triage_message:
          lang === "tl"
            ? "Nagkaroon ng error sa sistema. Kung lumalala ang sintomas, magpatingin agad sa doktor."
            : "There was an error in the system. If symptoms worsen, please see a doctor.",
        drug_interaction_warning: "None",
        red_flags:
          lang === "tl"
            ? [
                "Nagkaroon ng error sa pagsusuri. Kung may matinding sintomas, pumunta agad sa ospital.",
                disclaimerTl
              ]
            : [
                "There was an error analyzing your symptoms. If you have severe symptoms, go to the hospital immediately.",
                disclaimerEn
              ],
        conditions: []
      };
    }
  };

  if (!raw) return buildFallback();

  let str = typeof raw === "string" ? raw.trim() : JSON.stringify(raw);

  // 1) Direct parse
  try {
    return JSON.parse(str); // [web:37]
  } catch (_) {}

  // 2) Linisin `````` kung meron
  str = str.replace(/^``````$/i, "").trim();
  try {
    return JSON.parse(str);
  } catch (_) {}

  // 3) Manual partial extraction para ma-recover pa rin ang core fields [web:95]
  try {
    const obj = {
      urgency_level: "Self Care",
      triage_message: "",
      drug_interaction_warning: "None",
      red_flags: [],
      conditions: []
    };

    const getString = (key) => {
      const m = str.match(new RegExp(`"${key}"\\s*:\\s*"([^"]*)"`));
      return m ? m[1] : "";
    };

    const getArrayOfStrings = (key) => {
      const m = str.match(new RegExp(`"${key}"\\s*:\\s*\\[(.*?)\\]`, "s"));
      if (!m) return [];
      const inside = m[1];
      const items = inside
        .split(/,(?=(?:[^"]*"[^"]*")*[^"]*$)/) // split by comma, ignore commas inside quotes
        .map(s => s.trim())
        .filter(s => s.startsWith('"') && s.endsWith('"'))
        .map(s => s.slice(1, -1));
      return items;
    };

    obj.urgency_level = getString("urgency_level") || "Self Care";
    obj.triage_message = getString("triage_message") || "";
    obj.drug_interaction_warning = getString("drug_interaction_warning") || "None";
    obj.red_flags = getArrayOfStrings("red_flags");

    // Subukang i-parse ang conditions block kung kaya pa
    const condMatch = str.match(/"conditions"\s*:\s*\[(.*)\]\s*$/s);
    if (condMatch) {
      const condBlock = "[" + condMatch[1] + "]";
      try {
        const maybeConds = JSON.parse(condBlock);
        if (Array.isArray(maybeConds)) obj.conditions = maybeConds;
      } catch (_) {
        // ignore, leave conditions empty
      }
    }

    return obj;
  } catch (e) {
    console.error("Partial parse failed:", e.message);
  }

  // 4) Final fallback
  return buildFallback();
}

// ==========================================================
// 4. HELPER: CALL OPENAI WITH RETRY
// ==========================================================
async function callModelWithRetry(payload, numericStep, lang, maxRetries = 1) {
  for (let attempt = 0; attempt <= maxRetries; attempt++) {
    const completion = await client.chat.completions.create(payload);
    const reply = completion.choices[0]?.message?.content || "{}";

    console.log(`RAW REPLY (attempt ${attempt + 1}) >>>`, reply.slice(0, 400));

    const parsed = safeJsonParse(reply, numericStep, lang);
    // Kapag may kahit anong structure na (may 'urgency_level' o 'red_flags'), ok na
    if (parsed && (parsed.conditions || parsed.red_flags || parsed.urgency_level)) {
      return parsed;
    }

    console.warn("Parsed object still looks empty, retrying if may attempts pa...");
  }

  return safeJsonParse("{}", numericStep, lang);
}

// ==========================================================
// 5. MAIN ROUTE ( /analyze )
// ==========================================================
app.post("/analyze", async (req, res) => {
  const { step, age, gender, height, weight, bloodPressure, symptoms, answers } = req.body;

  if (!symptoms || symptoms.trim() === "") {
    return res.status(400).json({ error: "Symptoms are required" });
  }

  const numericStep = Number(step) || 2;
  const lang = detectLanguage(symptoms);
  console.log(`Input: "${symptoms}" | Detected: ${lang.toUpperCase()}`);

  try {
    let prompt = "";

    // STEP 1: FOLLOW-UP QUESTIONS
    if (numericStep === 1) {
      if (lang === "tl") {
        prompt = `
You are a medical AI assistant.
The user speaks TAGALOG.
Return ONLY valid JSON. No explanations, no markdown.

Goal: Generate 3 follow-up questions in TAGALOG based on symptoms.
User Info: ${age} years old, ${gender}.
Symptoms: "${symptoms}"

JSON format:
{
  "questions": [
    {
      "question": "Tanong sa Tagalog?",
      "options": ["Sagot 1", "Sagot 2", "Sagot 3", "Hindi ko alam"]
    }
  ]
}

Rules:
- Always return valid JSON.
- Do NOT include comments.
- Last option must be exactly "Hindi ko alam" or "Hindi sigurado".
        `.trim();
      } else {
        prompt = `
You are a medical AI assistant.
The user speaks ENGLISH.
Return ONLY valid JSON. No explanations, no markdown.

Goal: Generate 3 follow-up questions in ENGLISH based on symptoms.
User Info: ${age} years old, ${gender}.
Symptoms: "${symptoms}"

JSON format:
{
  "questions": [
    {
      "question": "Question in English?",
      "options": ["Option 1", "Option 2", "Option 3", "I don't know"]
    }
  ]
}

Rules:
- Always return valid JSON.
- Do NOT include comments.
- Last option must be exactly "I don't know".
        `.trim();
      }
    }

    // STEP 2: FINAL DIAGNOSIS & HOME REMEDIES
    else {
      const history = (answers || []).map(a => `Q: ${a.question}\nA: ${a.answer}`).join("\n");

      if (lang === "tl") {
        prompt = `
You are a medical AI assistant. HINDI ka doktor.
User Language: TAGALOG (use clear, conversational Tagalog).
Return ONLY valid JSON. No explanations, no markdown.

Task:
1. Analyze symptoms and give TOP 10 POSSIBLE CONDITIONS (ranked, most likely first).
2. For "treatment", provide CONCRETE, PRACTICAL HOME REMEDIES that fit the condition.
   - Examples: cold/warm compress, proper wound cleaning, elevation, eye shield, avoiding screen time,
     specific rest positions, breathing exercises, simple stretching, diet adjustments, over-the-counter
     pain relievers (e.g., paracetamol) kung walang allergy at ayon sa label.
3. You MAY mention common over-the-counter medicines (paracetamol, ibuprofen, oral rehydration solution)
   but NEVER:
   - give doses,
   - prescribe antibiotics, antivirals, chemotherapy, HIV medicines, or any controlled drugs.
4. For serious or chronic conditions (e.g., HIV/AIDS, cancer, stroke, heart attack, sepsis, meningitis,
   severe difficulty breathing, chest pain with shortness of breath, massive bleeding, loss of consciousness):
   - Do NOT list home remedies.
   - The "treatment" array should focus on going to ER, specialist clinic, or HIV/oncology clinic as soon as possible.
5. Add a triage tag "urgency_level": "Emergency", "Urgent Care", or "Self Care".
6. Add "triage_message": maikling paliwanag kung gaano kabilis dapat magpatingin at saan.
7. Add "drug_interaction_warning": kung may nabanggit na gamot, magbigay ng babala (hal. "Siguraduhing wala kang allergy,
   sundin ang label, at iwasan kung may sakit sa atay o bato."); kung wala, isulat ang "None".
8. Make each red flag a FULL sentence in Tagalog na madaling intindihin.

User: ${age}, ${gender}.
Height: ${height || "N/A"}
Weight: ${weight || "N/A"}
Blood Pressure: ${bloodPressure || "N/A"}
Symptoms: "${symptoms}"
History:
${history}

JSON format:
{
  "urgency_level": "Emergency" | "Urgent Care" | "Self Care",
  "triage_message": "Maikling paliwanag sa urgency sa Tagalog.",
  "drug_interaction_warning": "Babala sa gamot o 'None' kung wala.",
  "red_flags": [
    "Buong pangungusap na babala 1.",
    "Buong pangungusap na babala 2.",
    "Paalala: AI lang ito. Kumunsulta sa doktor."
  ],
  "conditions": [
    {
      "name": "Condition Name (English/Medical Term)",
      "overview": "Paliwanag sa Tagalog na simple at easy to understand.",
      "symptoms_matched": ["Sintomas 1 (Tagalog)", "Sintomas 2"],
      "treatment": [
        "Lunas sa bahay 1 (Tagalog, specific action o OTC kung angkop)",
        "Lunas 2",
        "Lunas 3",
        "Lunas 4",
        "Lunas 5"
      ],
      "match_level": "High" | "Medium" | "Low",
      "match_color": "#22c55e"
    }
  ]
}

Rules:
- Always return syntactically valid JSON (no comments, no trailing commas).
- If a condition is severe (e.g., HIV, cancer, stroke), focus treatment on immediate medical consultation,
  not home remedies.
        `.trim();
      } else {
        prompt = `
You are a medical AI assistant. NOT a doctor.
User Language: ENGLISH.
Return ONLY valid JSON. No explanations, no markdown.

Task:
1. Analyze symptoms and give up to 10 possible conditions (ranked).
2. For "treatment", provide CONCRETE, CONDITION-SPECIFIC HOME CARE STEPS, such as:
   - cold/warm compress, resting a specific body part, gentle stretching, avoiding certain activities,
     proper wound cleaning, over-the-counter pain relievers (e.g., paracetamol/acetaminophen) if no allergy,
     oral rehydration solution, lubricating eye drops, etc.
3. You MAY mention common over-the-counter medicines but MUST:
   - never give exact dosage,
   - never prescribe antibiotics, antivirals, chemotherapy, HIV medicines, or any controlled drugs.
4. For serious or chronic conditions (HIV/AIDS, cancers, stroke, heart attack, sepsis, meningitis,
   severe breathing problems, chest pain with shortness of breath, major trauma, etc.):
   - Do NOT list home remedies.
   - "treatment" should emphasize urgent in-person evaluation by a doctor or specialist.
5. Add "urgency_level": "Emergency", "Urgent Care", or "Self Care".
6. Add "triage_message": short explanation when and where to seek care.
7. Add "drug_interaction_warning": if any medicine is mentioned, warn about allergies, organ problems,
   and following package instructions; otherwise "None".
8. Make each red flag a FULL sentence in plain English.

User: ${age}, ${gender}.
Height: ${height || "N/A"}
Weight: ${weight || "N/A"}
Blood Pressure: ${bloodPressure || "N/A"}
Symptoms: "${symptoms}"
History:
${history}

JSON format:
{
  "urgency_level": "Emergency" | "Urgent Care" | "Self Care",
  "triage_message": "Brief explanation of urgency.",
  "drug_interaction_warning": "Warning text or 'None'.",
  "red_flags": [
    "Full warning sentence 1.",
    "Full warning sentence 2.",
    "Disclaimer: AI only. Consult a doctor."
  ],
  "conditions": [
    {
      "name": "Condition Name",
      "overview": "Description in simple English.",
      "symptoms_matched": ["Symptom 1", "Symptom 2"],
      "treatment": [
        "Home care step 1 (specific and practical).",
        "Home care step 2.",
        "Home care step 3.",
        "Home care step 4.",
        "Home care step 5."
      ],
      "match_level": "High" | "Medium" | "Low",
      "match_color": "#22c55e"
    }
  ]
}

Rules:
- Always return syntactically valid JSON (no comments, no trailing commas).
- For serious diseases like HIV or cancer, do NOT give home remedies; focus on seeing a doctor or specialist.
        `.trim();
      }
    }

    const payload = {
      model: "gpt-4o-mini",
      messages: [{ role: "user", content: prompt }],
      temperature: 0.3,
      max_tokens: 1500
      // response_format: { type: "json_object" }, // pwede mong i-on kapag supported
    };

    const parsed = await callModelWithRetry(payload, numericStep, lang, 1);

    // Final safety check
    if (numericStep === 2) {
      if (!Array.isArray(parsed.conditions)) parsed.conditions = [];
      if (!Array.isArray(parsed.red_flags)) {
        parsed.red_flags = [
          lang === "tl" ? "Nagkaroon ng error sa pagbabasa ng data." : "Error reading data."
        ];
      }
      if (!parsed.urgency_level) parsed.urgency_level = "Self Care";
      if (!parsed.triage_message) {
        parsed.triage_message =
          lang === "tl"
            ? "Nagkaroon ng error sa sistema. Kung lumalala, magpatingin sa doktor."
            : "There was a system error. If symptoms worsen, see a doctor.";
      }
      if (!parsed.drug_interaction_warning) parsed.drug_interaction_warning = "None";
    }

    res.json(parsed);

  } catch (err) {
    console.error("❌ Server Error:", err.message);
    res.status(500).json({ error: "Internal Server Error" });
  }
});

// ==========================================================
// 6. START SERVER
// ==========================================================
const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
  console.log(
    `✅ Server running at http://localhost:${PORT} (Language-Aware + Triage + Home Remedies + Partial JSON Recovery)`
  );
});
