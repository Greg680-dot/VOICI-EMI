import titles from './page-titles.json';

const page = (key, path, content) => ({
  path,
  title: titles[key],
  content,
});

export const homePage = page('accueil', '/', 'accueil');
export const initiativePage = page('initiative', '/initiative', 'initiative');
export const blogPage = page('blog', '/blog', 'blog');
export const formationPage = page('formation', '/formation', 'formation');
export const agendaPage = page('agenda', '/agenda', 'agenda');
export const articlePage = page('page_article', '/article', 'page_article');
export const attestationPage = page('page_attestation', '/page_attestation', 'page_attestation');
export const formationDetailPage = page('page_formation', '/formation-detail', 'page_formation');

const htmlLoaders = import.meta.glob('./content/*.html', {
  query: '?raw',
  import: 'default',
});
const scriptLoaders = import.meta.glob('./content/scripts/*.txt', {
  query: '?raw',
  import: 'default',
});

export async function loadPageContent(pageToLoad) {
  const htmlLoader = htmlLoaders[`./content/${pageToLoad.content}.html`];
  const scriptLoader = scriptLoaders[`./content/scripts/${pageToLoad.content}.txt`];

  if (!htmlLoader || !scriptLoader) {
    throw new Error(`Le contenu local de la page « ${pageToLoad.title} » est introuvable.`);
  }

  const [html, script] = await Promise.all([htmlLoader(), scriptLoader()]);
  return { html, script };
}