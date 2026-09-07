import type { ContentBody } from "@/lib/types";

export function ArticleBody({ body }: { body: ContentBody }) {
  return <div className="article-body">
    <p className="lede">{body.summary}</p>
    <p>{body.intro}</p>
    {body.sections?.map((s, i) => <section key={i}><h2>{s.heading}</h2><p>{s.body}</p></section>)}
    {!!body.takeaways?.length && <section className="takeaways"><h2>Key takeaways</h2><ul>{body.takeaways.map((x,i)=><li key={i}>{x}</li>)}</ul></section>}
    {!!body.faq?.length && <section><h2>Frequently asked questions</h2>{body.faq.map((f,i)=><div className="faq" key={i}><h3>{f.question}</h3><p>{f.answer}</p></div>)}</section>}
  </div>;
}
