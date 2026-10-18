import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

const common = {
  title: z.string().min(8).max(100),
  description: z.string().min(30).max(220),
  date: z.coerce.date(),
  updated: z.coerce.date().optional(),
  draft: z.boolean().default(false),
};

export const collections = {
  articles: defineCollection({
    loader: glob({ pattern: '**/*.md', base: './src/content/articles' }),
    schema: z.object({
      ...common,
      category: z.string().default('Field notes'),
    }),
  }),
  projects: defineCollection({
    loader: glob({ pattern: '**/*.md', base: './src/content/projects' }),
    schema: z.object({
      ...common,
      status: z.enum(['Planned', 'In progress', 'Completed']),
      technologies: z.array(z.string()).min(1),
      repository: z.string().url().optional(),
      demo: z.string().url().optional(),
      featured: z.boolean().default(false),
    }),
  }),
};
