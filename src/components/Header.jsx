import { useState } from 'react';

const links = [
  { label: 'Accueil', path: '/' },
  { label: "L'Initiative", path: '/initiative' },
  { label: 'Blog', path: '/blog' },
  { label: 'Formations', path: '/formation' },
  { label: 'Agenda', path: '/agenda' },
];

function Header({ currentPath = '/' }) {
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <header className="site-header">
      <div className="site-header__inner">
        <a className="site-brand" href="/" aria-label="VoiCI-EMI Afrique, accueil">
          <span className="site-brand__name">
            VoiCI-EMI
          </span>
          <span className="site-brand__tagline">
            Éducation aux Médias &amp; Citoyenneté
          </span>
        </a>

        <button
          className="menu-toggle"
          type="button"
          aria-expanded={menuOpen}
          aria-controls="primary-navigation"
          onClick={() => setMenuOpen((open) => !open)}
        >
          <span className="material-symbols-outlined" aria-hidden="true">
            menu
          </span>
          <span className="sr-only">Ouvrir le menu</span>
        </button>

        <nav
          className={`site-nav${menuOpen ? ' site-nav--open' : ''}`}
          id="primary-navigation"
          aria-label="Navigation principale"
        >
          {links.map((link) => (
            <a
              key={link.path}
              href={link.path}
              aria-current={currentPath === link.path ? 'page' : undefined}
              onClick={() => setMenuOpen(false)}
            >
              {link.label}
            </a>
          ))}
          <a
            className="site-nav__mobile-cta"
            href="/page_attestation"
            onClick={() => setMenuOpen(false)}
          >
            Guichet Vérification
          </a>
        </nav>

        <a className="site-header__cta" href="/page_attestation">
          <span className="material-symbols-outlined" aria-hidden="true">
            verified
          </span>
          <span>
            <strong>Guichet Vérification</strong>
            <small>Signalement &amp; Fact-Checking</small>
          </span>
        </a>
      </div>
    </header>
  );
}

export default Header;
