import express from "express";
import cors from "cors";
import dotenv from "dotenv";
import OpenAI from "openai";

// Load environment variables
dotenv.config();

const app = express();
app.use(cors());
app.use(express.json());

// --- V2 LOGGING: Para alam natin na V2 ang tumatakbo ---
console.log("🚀 GabayAI Server V2 (Triage Edition) is initializing...");

if (!process.env.OPENAI_API_KEY) {
  console.error("❌ FATAL ERROR: Missing OPENAI_API_KEY in .env");
  process.exit(1);
}

const client = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY,
});

app.post("/analyze", async (req, res) => {
  // Tanggapin ang data galing sa Frontend V2
  const { age, gender, height, weight, bloodPressure, symptoms } = req.body;

  // Debug log para makita kung pumapasok ang "Detailed Symptoms"
  console.log("📥 V2 Analysis Request Received:");
  console.log(`   User Profile: ${age}yo / ${gender} / BP: ${bloodPressure}`);
  console.log(`   Symptoms: "${symptoms}"`);

  if (!symptoms || symptoms.trim() === "") {
    return res.status(400).json({ error: "Symptoms are required" });
  }

  try {
    // --- V2 PROMPT ENGINEERING: THE "TRIAGE DOCTOR" PERSONA ---
    // Dito natin ituturo sa AI na maging specific at hindi generic.
    const prompt = `
    ROLE: You are an advanced Medical Triage AI (GabayAI V2) for the Philippines.
    OBJECTIVE: Analyze detailed symptoms to provide a precise differential diagnosis.

    PATIENT PROFILE:
    - Age: ${age || "Unknown"}
    - Gender: ${gender || "Unknown"}
    - BP: ${bloodPressure || "Unknown"}
    
    USER COMPLAINT: "${symptoms}"
    (Note: The input may contain specific qualifiers like "throbbing", "squeezing", "one-sided" which are critical for diagnosis.)

    INSTRUCTIONS:
    1. LANGUAGE: Reply in the SAME LANGUAGE as the user (Tagalog/English/Taglish).
    2. PRECISION: Focus on the specific details provided. If the user says "throbbing one-sided headache", prioritize "Migraine" and DO NOT suggest "Tension Headache" unless ambiguity exists.
    3. QUANTITY: Provide only the **TOP 3** most likely conditions. (7 is too many and confusing).
    4. REMEDIES: Provide actionable, safe, home-based care. Avoid prescription-only meds unless advising to consult a doctor.

    OUTPUT FORMAT (Strict JSON):
    {
      "conditions": [
        { 
          "name": "Medical Term (Common Name)", 
          "remedy": "• Specific Step 1\n• Specific Step 2\n• Diet/Lifestyle Tip" 
        }
      ],
      "red_flags": [
        "Urgent Warning 1",
        "Urgent Warning 2 (Go to ER if...)"
      ]
    }
    `;

    // Call OpenAI API
    const completion = await client.chat.completions.create({
      model: "gpt-4o-mini", // Mas mabilis at matalino para sa short tasks
      messages: [{ role: "user", content: prompt }],
      temperature: 0.2, // Lower temperature = Mas strict at hindi "creative" (Good for medical)
    });

    const reply = completion.choices[0].message.content;

    // --- V2 JSON CLEANER ---
    // Tatanggalin ang mga `````` na minsan sinasama ni GPT
    const cleanReply = reply.replace(/``````/g, '').trim();

    let parsed;
    try {
      parsed = JSON.parse(cleanReply);
    } catch (e) {
      console.error("⚠️ JSON Parse Error (V2):", e.message);
      console.error("Raw Reply:", cleanReply);
      // Fallback kung sakaling sumabog ang JSON
      parsed = {
        conditions: [
          { name: "Analysis Incomplete", remedy: "• Please try describing your symptoms again clearly." }
        ],
        red_flags: ["System could not process the specific diagnosis."]
      };
    }

    // Send response back to frontend
    res.json(parsed);

  } catch (err) {
    console.error("❌ Server V2 Error:", err.message);
    res.status(500).json({ error: "AI analysis failed" });
  }
});

const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
  console.log(`✅ GabayAI Server V2 is running on http://localhost:${PORT}`);
  console.log(`   (Ready to triage detailed symptoms)`);
});
