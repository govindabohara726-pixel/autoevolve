type Message = { role: "system" | "user" | "assistant"; content: string };

export async function aiComplete(messages: Message[], temperature = 0.3) {
  const base = (process.env.AI_BASE_URL || "https://api.deepseek.com").replace(/\/$/, "");
  const key = process.env.AI_API_KEY;
  const model = process.env.AI_MODEL || "deepseek-chat";
  if (!key) throw new Error("AI_API_KEY is missing.");

  const res = await fetch(`${base}/chat/completions`, {
    method: "POST",
    headers: {
      "content-type": "application/json",
      authorization: `Bearer ${key}`
    },
    body: JSON.stringify({ model, messages, temperature, response_format: { type: "json_object" } }),
    cache: "no-store"
  });

  if (!res.ok) {
    const text = await res.text();
    throw new Error(`AI provider error ${res.status}: ${text.slice(0, 500)}`);
  }

  const json = await res.json();
  return String(json?.choices?.[0]?.message?.content || "{}");
}

export function parseAIJson<T>(raw: string): T {
  const cleaned = raw.trim().replace(/^```json\s*/i, "").replace(/```$/, "").trim();
  return JSON.parse(cleaned) as T;
}
