import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Header } from "@/components/Header";
import { ArticleBody } from "@/components/ArticleBody";
import { getPublishedArticle } from "@/lib/backend";
import { SITE } from "@/lib/site";

type Props={params:Promise<{slug:string}>};
export async function generateMetadata({params}:Props):Promise<Metadata>{const {slug}=await params;const a=await getPublishedArticle(slug);if(!a)return{};return{title:a.meta_title||a.title,description:a.meta_description||a.excerpt||undefined,alternates:{canonical:`/articles/${a.slug}`},openGraph:{title:a.meta_title||a.title,description:a.meta_description||a.excerpt||undefined,type:"article"}}}
export default async function ArticlePage({params}:Props){const {slug}=await params;const a=await getPublishedArticle(slug);if(!a)notFound();const schema={"@context":"https://schema.org","@type":"Article",headline:a.title,description:a.meta_description||a.excerpt,datePublished:a.published_at,dateModified:a.updated_at,mainEntityOfPage:`${SITE.url}/articles/${a.slug}`};return <><Header/><article className="article"><script type="application/ld+json" dangerouslySetInnerHTML={{__html:JSON.stringify(schema)}}/><span className="eyebrow">{a.primary_keyword||"GUIDE"}</span><h1>{a.title}</h1><p className="meta">Updated {new Date(a.updated_at).toLocaleDateString()}</p><ArticleBody body={a.body}/></article></>}
