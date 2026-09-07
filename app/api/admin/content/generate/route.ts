import { NextResponse } from "next/server";
import { requireAdmin } from "@/lib/auth";
import { generateContent } from "@/lib/ai/engine";
export async function POST(req:Request){try{await requireAdmin();const {topic,keyword}=await req.json();if(!topic||!keyword)return NextResponse.json({error:"topic and keyword are required"},{status:400});const data=await generateContent(String(topic),String(keyword));return NextResponse.json({ok:true,data})}catch(e){return NextResponse.json({error:e instanceof Error?e.message:"Failed"},{status:500})}}
