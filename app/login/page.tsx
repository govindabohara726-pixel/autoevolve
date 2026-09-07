"use client";
import Link from "next/link";
import { useState } from "react";
import { createClient } from "@/lib/supabase/client";

export default function Login() {
  const [msg, setMsg] = useState("");
  return <main className="login-wrap"><div className="login"><h1>Admin sign in</h1><p className="muted">Use the Supabase user you promoted to the admin role.</p><form onSubmit={async e=>{e.preventDefault();const fd=new FormData(e.currentTarget);setMsg("Signing in…");try{const db=createClient();const {error}=await db.auth.signInWithPassword({email:String(fd.get("email")),password:String(fd.get("password"))});if(error)return setMsg(error.message);location.href="/admin"}catch{setMsg("Supabase is not configured yet. Open the setup page first.")}}}><input name="email" type="email" placeholder="Email" required/><input name="password" type="password" placeholder="Password" required/><button className="button">Sign in</button><span>{msg}</span></form><p style={{marginTop:18}}><Link href="/setup">Check deployment setup →</Link></p></div></main>;
}
