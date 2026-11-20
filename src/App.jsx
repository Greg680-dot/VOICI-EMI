import { useEffect, useState } from 'react';
import Header from './components/Header.jsx';
import Footer from './components/Footer.jsx';
import PageContent from './components/PageContent.jsx';
import {
  agendaPage,
  articlePage,
  attestationPage,
  blogPage,
  formationDetailPage,
  formationPage,
  homePage,
  initiativePage,
  loadPageContent,
} from './pages.js';

const pages = [
  homePage,
  initiativePage,
  blogPage,
  formationPage,
  agendaPage,
  articlePage,
  attestationPage,
  formationDetailPage,
];

const pageAliases = {
  '/accueil': homePage,
  '/blog-decryptages': blogPage,
  '/formations': formationPage,
  '/rencontres-actus': agendaPage,
  '/article': articlePage,
  '/page_article': articlePage,
  '/attestation': attestationPage,
  '/page_formation': formationDetailPage,
};

function PageRoute({ page }) {
  const [content, setContent] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    let active = true;
    loadPageContent(page).then(
      (loadedContent) => {
        if (active) setContent(loadedContent);
      },
      (loadError) => {
        if (active) setError(loadError);
      },
    );

    return () => {
      active = false;
    };
  }, [page]);

  if (error) {
    return (
      <main className="not-found" role="alert">
        <h1>Impossible de charger cette page</h1>
        <p>{error.message}</p>
        <a href="/">Retour à l’accueil</a>
      </main>
    );
  }

  if (!content) {
    return (
      <main className="page-loading" aria-live="polite">
        Chargement…
      </main>
    );
  }

  return <PageContent page={{ ...page, ...content }} />;
}

function App() {
  const pathname = window.location.pathname.replace(/\/+$/, '') || '/';
  const page = pages.find((candidate) => candidate.path === pathname) ?? pageAliases[pathname];

  if (!page) {
    return (
      <>
        <Header />
        <main className="not-found">
          <h1>Page introuvable</h1>
          <a href="/">Retour à l’accueil</a>
        </main>
        <Footer />
      </>
    );
  }

  return (
    <>
      <Header currentPath={page.path} />
      <PageRoute page={page} />
      <Footer />
    </>
  );
}

export default App;
