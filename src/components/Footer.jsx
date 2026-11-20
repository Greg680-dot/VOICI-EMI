const footerLinks = [
  {
    title: 'La plateforme',
    links: [
      ['Accueil', '/'],
      ["L'Initiative", '/initiative'],
      ['Blog & Décryptages', '/blog'],
      ['Rencontres & Actus', '/agenda'],
    ],
  },
  {
    title: 'Se former',
    links: [
      ['Formations', '/formation'],
      ['Parcours de formation', '/formation-detail'],
      ['Attestations', '/page_attestation'],
    ],
  },
  {
    title: 'Vérification',
    links: [
      ['Guichet Vérification', '/page_attestation'],
      ['Déposer un signalement', '/page_attestation'],
      ['Charte déontologique', '/initiative'],
    ],
  },
];

function Footer() {
  return (
    <footer className="site-footer">
      <div className="site-footer__inner">
        <div className="site-footer__intro">
          <a className="site-footer__brand" href="/">
            VoiCI-EMI Afrique
          </a>
          <p>
            Plateforme panafricaine dédiée au renforcement de l’esprit critique,
            à l’éducation aux médias et à l’information.
          </p>
          <span className="site-footer__badge">Réseau d’intégrité panafricain</span>
        </div>

        {footerLinks.map((column) => (
          <nav className="site-footer__column" key={column.title}>
            <h2>{column.title}</h2>
            {column.links.map(([label, path]) => (
              <a href={path} key={label}>
                {label}
              </a>
            ))}
          </nav>
        ))}
      </div>
      <div className="site-footer__bottom">
        <span>© {new Date().getFullYear()} VoiCI-EMI Afrique</span>
        <a href="/initiative">Mentions légales &amp; protection des données</a>
      </div>
    </footer>
  );
}

export default Footer;
