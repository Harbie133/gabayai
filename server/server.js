import express from "express";
import cors from "cors";
import dotenv from "dotenv";
import OpenAI from "openai";

dotenv.config();

const app = express();
app.use(cors());
app.use(express.json());

if (!process.env.OPENAI_API_KEY) {
  console.error("❌ Missing OPENAI_API_KEY in .env");
  process.exit(1);
}

const client = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY,
});

app.post("/analyze", async (req, res) => {
  const { age, gender, height, weight, bloodPressure, symptoms } = req.body;

  if (!symptoms || symptoms.trim() === "") {
    return res.status(400).json({ error: "Symptoms are required" });
  }

  try {
    const prompt = `
You are a medical assistant AI. 
User may describe symptoms in English or Tagalog. Detect the language automatically.
Respond ONLY in the SAME LANGUAGE as the user's input in JSON format.

Guidelines:
- Suggest 7 most likely conditions (common medical terms, short names).
- For each condition, provide a **detailed and specific self-care or medical remedy** in bullet points, including steps, dosages (if relevant), lifestyle tips, or dietary suggestions.
- Include possible red flags indicating urgent care.
- Do not provide explanations outside the JSON.

Patient details:
- Age: ${age || "N/A"}
- Gender: ${gender || "N/A"}
- Height: ${height || "N/A"}
- Weight: ${weight || "N/A"}
- Blood Pressure: ${bloodPressure || "N/A"}
- Symptoms: ${symptoms}

Respond in JSON ONLY:
{
  "conditions": [
    {"name": "Condition1", "remedy": "- Step 1\\n- Step 2"},
    {"name": "Condition2", "remedy": "- Step 1\\n- Step 2"},
    {"name": "Condition3", "remedy": "- Step 1\\n- Step 2"},
    {"name": "Condition4", "remedy": "- Step 1\\n- Step 2"},
    {"name": "Condition5", "remedy": "- Step 1\\n- Step 2"},
    {"name": "Condition6", "remedy": "- Step 1\\n- Step 2"},
    {"name": "Condition7", "remedy": "- Step 1\\n- Step 2"}
  ],
  "red_flags": ["Red flag 1", "Red flag 2", "Red flag 3"]
}
`;

    const completion = await client.chat.completions.create({
      model: "gpt-4o-mini",
      messages: [{ role: "user", content: prompt }],
      temperature: 0.3,
    });

    const reply = completion.choices[0].message.content;

    let parsed;
    try {
      parsed = JSON.parse(reply);
    } catch {
      parsed = {
        conditions: Array.from({ length: 7 }, () => ({
          name: "Unknown",
          remedy: "- Consult a doctor for detailed advice"
        })),
        red_flags: ["Unable to parse AI response"],
      };
    }

    res.json(parsed);
  } catch (err) {
    console.error("❌ Server error:", err.message);
    res.status(500).json({ error: "AI analysis failed" });
  }
});

const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
  console.log(`✅ Server running at http://localhost:${PORT}`);
});

