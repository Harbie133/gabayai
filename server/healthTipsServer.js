import express from "express";
import cors from "cors";
import { GoogleGenerativeAI } from "@google/generative-ai";

// --- BASIC SERVER SETUP ---
const app = express();
app.use(cors());
app.use(express.json());

// --- GEMINI SETUP ---

// Dapat naka-set sa .env at sa Render env vars:
const GEMINI_API_KEY = process.env.GEMINI_API_KEY;

if (!GEMINI_API_KEY) {
  console.error("❌ GEMINI_API_KEY is not set. Please set it in your environment variables.");
  process.exit(1);
}

const MODEL_NAME = "gemini-2.0-flash-001";

const genAI = new GoogleGenerativeAI(GEMINI_API_KEY);
const model = genAI.getGenerativeModel({ model: MODEL_NAME });

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

    // New @google/generative-ai call pattern
    const result = await model.generateContent(prompt);
    const response = await result.response;
    const text = response.text();

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
