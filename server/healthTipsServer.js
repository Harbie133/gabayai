import express from "express";
import cors from "cors";
import { GoogleGenAI } from "@google/genai";

// --- BASIC SERVER SETUP ---
const app = express();
app.use(cors());
app.use(express.json());

// --- GEMINI SETUP ---
// Para sa dev, pwede mo munang ilagay direkta yung key dito.
// Mas maganda long-term: process.env.GEMINI_API_KEY
const GEMINI_API_KEY = process.env.GEMINI_API_KEY || "AIzaSyDo-t8o02JFU_qSSrrm2m-z_czcSe43UtA";

if (!GEMINI_API_KEY || GEMINI_API_KEY === "YOUR_REAL_GEMINI_KEY_HERE") {
  console.warn("⚠️ GEMINI_API_KEY is not set or still default. Please set it before using /api/health-tips.");
}

const ai = new GoogleGenAI({ apiKey: GEMINI_API_KEY });

// Piliin mo kung anong model ang available sa key mo.
// Safe choices: 'gemini-2.0-flash-001' o 'gemini-1.5-flash'
const MODEL_NAME = "gemini-2.0-flash-001";

function buildHealthTipsPrompt(topic, languageMode) {
  return `
You are a careful health educator. Provide general first aid and home health tips only.
Do NOT diagnose diseases, do NOT provide medication names or dosages, and do NOT give individualized medical advice.
Always remind the reader to consult a licensed doctor or visit the nearest clinic or emergency room for serious symptoms.

Topic: "${topic}"
Language mode: "${languageMode}" ("tagalog" | "english" | "both").

Return STRICT JSON ONLY, no extra text, with this exact shape:

{
  "title": "short readable title",
  "tagalog_paragraphs": ["..."],
  "english_paragraphs": ["..."],
  "disclaimer": "..."
}

Rules:
- If languageMode is "tagalog", fill tagalog_paragraphs and leave english_paragraphs as [].
- If languageMode is "english", fill english_paragraphs and leave tagalog_paragraphs as [].
- If languageMode is "both", fill both arrays.
- Use short paragraphs (2–4 sentences each).
- Tagalog text should be simple and conversational (Philippines).
`;
}

// --- ROUTE: AI Health Tips ---
app.post("/api/health-tips", async (req, res) => {
  try {
    const { topic, language = "both" } = req.body || {};
    if (!topic) {
      return res.status(400).json({ error: "Topic is required" });
    }

    const prompt = buildHealthTipsPrompt(topic, language);

    const response = await ai.models.generateContent({
      model: MODEL_NAME,
      contents: [
        {
          role: "user",
          parts: [{ text: prompt }],
        },
      ],
    });

    const text = response.text;
    let parsed;
    try {
      parsed = JSON.parse(text);
    } catch (err) {
      console.error("JSON parse error from Gemini, raw output:", text);
      return res.status(500).json({ error: "AI response format error" });
    }

    if (!parsed.title || !parsed.disclaimer) {
      return res.status(500).json({ error: "Incomplete AI response" });
    }

    res.json({
      title: parsed.title,
      tagalog_paragraphs: parsed.tagalog_paragraphs || [],
      english_paragraphs: parsed.english_paragraphs || [],
      disclaimer: parsed.disclaimer,
    });
  } catch (err) {
    console.error("❌ HealthTips AI error:", err);
    res.status(500).json({ error: "Health tips AI failed" });
  }
});

// --- START SERVER ---
const PORT = process.env.HEALTH_TIPS_PORT || 5100;
app.listen(PORT, () => {
  console.log(`✅ Health Tips server running at http://localhost:${PORT}`);
});
