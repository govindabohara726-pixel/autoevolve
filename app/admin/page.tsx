import { redirect } from "next/navigation";
import { backendUrl } from "@/lib/backend";
export const dynamic="force-dynamic";
export default function AdminRedirect(){const base=backendUrl();if(!base)redirect('/setup');redirect(`${base}/admin`)}
