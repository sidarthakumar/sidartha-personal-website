import { articles } from '../lib/content';
const escape = (value: string) =>
  value.replace(
    /[<>&"']/g,
    (char) =>
      ({
        '<': '&lt;',
        '>': '&gt;',
        '&': '&amp;',
        '"': '&quot;',
        "'": '&apos;',
      })[char]!,
  );
export async function GET() {
  const entries = await articles();
  const items = entries
    .map(
      (item) =>
        `<item><title>${escape(item.data.title)}</title><link>https://sidarthakumar.com/${item.id}/</link><guid isPermaLink="true">https://sidarthakumar.com/${item.id}/</guid><pubDate>${item.data.date.toUTCString()}</pubDate><description>${escape(item.data.description)}</description></item>`,
    )
    .join('');
  const xml = `<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>Sidartha Kumar — Insights</title><link>https://sidarthakumar.com/insights/</link><description>Notes from development, practical projects, and learning.</description><language>en</language>${items}</channel></rss>`;
  return new Response(xml, {
    headers: { 'Content-Type': 'application/rss+xml; charset=utf-8' },
  });
}
