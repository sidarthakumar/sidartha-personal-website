import { getCollection } from 'astro:content';

export const articles = async () =>
  (await getCollection('articles', ({ data }) => !data.draft)).sort(
    (a, b) => b.data.date.valueOf() - a.data.date.valueOf(),
  );
export const projects = async () =>
  (await getCollection('projects', ({ data }) => !data.draft)).sort(
    (a, b) =>
      Number(b.data.featured) - Number(a.data.featured) ||
      b.data.date.valueOf() - a.data.date.valueOf(),
  );
export const displayDate = (date: Date) =>
  new Intl.DateTimeFormat('en', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
  }).format(date);
export const readingTime = (body: string = '') =>
  Math.max(1, Math.ceil(body.split(/\s+/).length / 220));
