"use client";
import { useState } from "react";

async function post(url: string, body?: unknown) {
  const res = await fetch(url, { method: "POST", headers: { "content-type": "application/json" }, body: JSON.stringify(body || {}) });
  const json = await res.json();
  if (!res.ok) throw new Error(json.error || "Request failed");
  return json;
}

export function RunEvolutionButton() {
  const [state, setState] = useState("Run evolution cycle");
  return <button className="button" onClick={async()=>{try{setState("Running…"); await post("/api/cron/evolve", { adminTrigger: true }); setState("Completed ✓"); location.reload();}catch(e){setState(e instanceof Error ? e.message : "Failed")}}}>{state}</button>;
}

export function GenerateContentForm() {
  const [state, setState] = useState("");
  return <form className="inline-form" onSubmit={async e=>{e.preventDefault(); const form=new FormData(e.currentTarget); try{setState("Generating…"); await post("/api/admin/content/generate", {topic: form.get("topic"), keyword: form.get("keyword")}); setState("Draft generated ✓"); (e.currentTarget as HTMLFormElement).reset();}catch(err){setState(err instanceof Error?err.message:"Failed")}}}>
    <input name="topic" placeholder="Topic, e.g. Best budgeting apps" required />
    <input name="keyword" placeholder="Primary keyword" required />
    <button className="button">Generate draft</button><span>{state}</span>
  </form>;
}

export function ApproveButton({ actionId }: { actionId: string }) {
  const [state,setState]=useState("Approve & publish");
  return <button className="button small" onClick={async()=>{try{setState("Publishing…"); await post("/api/admin/content/approve",{actionId}); setState("Published ✓"); location.reload();}catch(e){setState(e instanceof Error?e.message:"Failed")}}}>{state}</button>;
}
