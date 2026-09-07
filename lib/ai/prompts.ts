export const contentSystem = `You are the editorial engine for a high-trust decision website.
Write concise, useful, original content for humans first. Never fabricate facts, prices, statistics, quotes, or firsthand testing. If fresh external facts are required but unavailable, explicitly flag them for research instead of inventing them.
Return valid JSON only.`;

export function generatePrompt(topic: string, keyword: string) {
  return `Create an evergreen decision-guide article about: ${topic}\nPrimary keyword: ${keyword}\n
Return JSON with exactly this shape:
{
 "title":"",
 "slug":"lowercase-hyphen-slug",
 "excerpt":"",
 "meta_title":"max ~60 chars",
 "meta_description":"max ~155 chars",
 "search_intent":"informational|commercial|transactional|navigational",
 "body":{
   "summary":"",
   "intro":"",
   "sections":[{"heading":"","body":""}],
   "faq":[{"question":"","answer":""}],
   "takeaways":[""]
 }
}
Include 4-7 useful sections and 3-5 FAQs. Avoid filler.`;
}

export function improvePrompt(input: unknown, reason: string) {
  return `Improve the following existing article because: ${reason}.
Preserve its core URL intent. Do not invent current facts. Improve clarity, usefulness, search intent coverage, headings, FAQ quality and conversion to a decision.
Return the same JSON article shape used by the generator.\n\nARTICLE:\n${JSON.stringify(input)}`;
}

export function opportunitiesPrompt(existingTitles: string[]) {
  return `Given these existing article titles:\n${existingTitles.join("\n")}\n
Find 5 adjacent high-intent content opportunities that would strengthen topical authority. Do not claim search volume you cannot verify.
Return JSON: {"opportunities":[{"topic":"","keyword":"","type":"comparison|alternatives|best|guide|tool","reason":"","priority":1}]} where priority is 1-100.`;
}
