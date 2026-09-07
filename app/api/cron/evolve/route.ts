import { NextResponse } from "next/server";
import { runEvolutionCycle } from "@/lib/evolution";
import { createClient } from "@/lib/supabase/server";
import { hasAIConfig, hasSupabaseAdminConfig, hasSupabasePublicConfig } from "@/lib/config";

async function isAdminRequest() {
  if (!hasSupabasePublicConfig()) return false;
  try {
    const db = await createClient();
    const { data } = await db.auth.getClaims();
    if (!data?.claims?.sub) return false;
    const { data: p } = await db
      .from("profiles")
      .select("role")
      .eq("id", data.claims.sub)
      .single();
    return p?.role === "admin";
  } catch {
    return false;
  }
}

export async function POST(req: Request) {
  const missing: string[] = [];
  if (!hasSupabaseAdminConfig()) missing.push("Supabase environment variables");
  if (!hasAIConfig()) missing.push("AI_API_KEY");
  if (missing.length) {
    return NextResponse.json(
      { error: "AutoEvolve is not fully configured.", missing },
      { status: 503 }
    );
  }

  const auth = req.headers.get("authorization");
  const cronOk = !!process.env.CRON_SECRET && auth === `Bearer ${process.env.CRON_SECRET}`;
  const adminOk = await isAdminRequest();
  if (!cronOk && !adminOk) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  try {
    return NextResponse.json({ ok: true, result: await runEvolutionCycle() });
  } catch (e) {
    return NextResponse.json(
      { error: e instanceof Error ? e.message : "Cycle failed" },
      { status: 500 }
    );
  }
}

export async function GET(req: Request) {
  return POST(req);
}
