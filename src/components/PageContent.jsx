import { useEffect, useRef } from 'react';

const pagePaths = {
  accueil: '/',
  'l-initiative': '/initiative',
  'blog-decryptages': '/blog',
  formations: '/formation',
  'rencontres-actus': '/agenda',
  'guichet-verification': '/page_attestation',
  'charte-deontologique': '/initiative',
  'page_article': '/article',
  'page-article': '/article',
  'page_formation': '/formation-detail',
  'page-formation': '/formation-detail',
  attestation: '/page_attestation',
};

function PageContent({ page }) {
  const contentRef = useRef(null);

  useEffect(() => {
    document.title = `${page.title} | VoiCI-EMI Afrique`;

    contentRef.current
      ?.querySelectorAll('a[data-path]')
      .forEach((anchor) => {
        const path = pagePaths[anchor.dataset.path];
        if (path) anchor.href = path;
      });

    if (!page.script.trim()) return undefined;

    const script = document.createElement('script');
    script.textContent = page.script;
    document.body.appendChild(script);

    return () => script.remove();
  }, [page]);

  return (
    <main className="page-content" ref={contentRef}>
      <div dangerouslySetInnerHTML={{ __html: page.html }} />
    </main>
  );
}

export default PageContent;
