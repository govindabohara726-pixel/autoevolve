import { NextResponse } from "next/server";
import { requireAdmin } from "@/lib/auth";
import { improveContent } from "@/lib/ai/engine";
export async function POST(req:Request){try{await requireAdmin();const {contentId,reason}=await req.json();if(!contentId)return NextResponse.json({error:"contentId is required"},{status:400});const data=await improveContent(String(contentId),String(reason||"Manual improvement request"),false);return NextResponse.json({ok:true,data})}catch(e){return NextResponse.json({error:e instanceof Error?e.message:"Failed"},{status:500})}}
