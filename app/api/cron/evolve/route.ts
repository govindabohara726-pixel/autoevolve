import { NextResponse } from "next/server";
import { runEvolutionCycle } from "@/lib/evolution";
import { createClient } from "@/lib/supabase/server";
async function isAdminRequest(req:Request){try{const db=await createClient();const {data}=await db.auth.getClaims();if(!data?.claims?.sub)return false;const {data:p}=await db.from("profiles").select("role").eq("id",data.claims.sub).single();return p?.role==="admin"}catch{return false}}
export async function POST(req:Request){const auth=req.headers.get("authorization");const cronOk=!!process.env.CRON_SECRET&&auth===`Bearer ${process.env.CRON_SECRET}`;const adminOk=await isAdminRequest(req);if(!cronOk&&!adminOk)return NextResponse.json({error:"Unauthorized"},{status:401});try{return NextResponse.json({ok:true,result:await runEvolutionCycle()})}catch(e){return NextResponse.json({error:e instanceof Error?e.message:"Cycle failed"},{status:500})}}
export async function GET(req:Request){return POST(req)}
