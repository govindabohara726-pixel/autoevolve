import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { hasSupabasePublicConfig } from "@/lib/config";

export async function requireAdmin() {
  if (!hasSupabasePublicConfig()) redirect("/setup");

  let supabase;
  try {
    supabase = await createClient();
  } catch {
    redirect("/setup?error=supabase");
  }

  const { data, error } = await supabase.auth.getClaims();
  const userId = data?.claims?.sub;
  if (error || !userId) redirect("/login");

  const { data: profile } = await supabase
    .from("profiles")
    .select("role")
    .eq("id", userId)
    .single();

  if (profile?.role !== "admin") redirect("/login?error=not-admin");
  return { userId, supabase };
}
