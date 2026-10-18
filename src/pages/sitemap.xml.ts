import { articles, projects } from '../lib/content';
export async function GET() {
  const entries = [
    ...[
      '/',
      '/about/',
      '/skills/',
      '/projects/',
      '/journey/',
      '/insights/',
      '/contact/',
      '/privacy-policy/',
    ].map((path) => ({ path, modified: undefined as Date | undefined })),
    ...(await articles()).map((item) => ({
      path: `/${item.id}/`,
      modified: item.data.updated || item.data.date,
    })),
    ...(await projects()).map((item) => ({
      path: `/project/${item.id}/`,
      modified: item.data.updated || item.data.date,
    })),
  ];
  const xml =
    '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' +
    entries
      .map(
        (item) =>
          `<url><loc>https://sidarthakumar.com${item.path}</loc>${item.modified ? `<lastmod>${item.modified.toISOString().slice(0, 10)}</lastmod>` : ''}</url>`,
      )
      .join('') +
    '</urlset>';
  return new Response(xml, {
    headers: { 'Content-Type': 'application/xml; charset=utf-8' },
  });
}
