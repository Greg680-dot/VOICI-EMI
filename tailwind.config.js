const theme = {
  "colors": {
    "surface-container-low": "#eff4ff",
    "outline": "#737784",
    "secondary": "#006c49",
    "badge-orange-bg": "#FFF7ED",
    "tertiary-fixed": "#dae2ff",
    "on-surface": "#0b1c30",
    "on-secondary-fixed-variant": "#005236",
    "on-primary-fixed-variant": "#00429b",
    "surface-container": "#e5eeff",
    "surface-dim": "#cbdbf5",
    "secondary-fixed": "#6ffbbe",
    "surface-card": "#FFFFFF",
    "on-tertiary-fixed": "#001946",
    "error-container": "#ffdad6",
    "inverse-primary": "#b0c6ff",
    "badge-green-bg": "#ECFDF5",
    "inverse-surface": "#213145",
    "primary-fixed-dim": "#b0c6ff",
    "secondary-container": "#6cf8bb",
    "badge-rose-text": "#9D174D",
    "error": "#ba1a1a",
    "on-tertiary-fixed-variant": "#2b457e",
    "on-error": "#ffffff",
    "badge-rose-bg": "#FDF2F8",
    "on-secondary-container": "#00714d",
    "tertiary-fixed-dim": "#b1c5ff",
    "surface": "#f8f9ff",
    "inverse-on-surface": "#eaf1ff",
    "tertiary-container": "#354e88",
    "warm-canvas": "#FAFCFF",
    "secondary-fixed-dim": "#4edea3",
    "badge-orange-text": "#9A3412",
    "on-primary-container": "#a9c1ff",
    "on-background": "#0b1c30",
    "surface-container-lowest": "#ffffff",
    "badge-blue-text": "#003A88",
    "on-tertiary": "#ffffff",
    "vitality-rose": "#ED0E64",
    "surface-variant": "#d3e4fe",
    "surface-container-highest": "#d3e4fe",
    "tertiary": "#1b3670",
    "primary-container": "#004aad",
    "badge-green-text": "#065F46",
    "on-secondary": "#ffffff",
    "on-primary": "#ffffff",
    "solar-orange": "#F68712",
    "badge-blue-bg": "#EBF3FC",
    "primary": "#00357f",
    "on-secondary-fixed": "#002113",
    "on-surface-variant": "#434653",
    "on-primary-fixed": "#001945",
    "surface-tint": "#215abd",
    "on-tertiary-container": "#abc1ff",
    "outline-variant": "#c3c6d5",
    "background": "#f8f9ff",
    "surface-bright": "#f8f9ff",
    "primary-fixed": "#d9e2ff",
    "border-subtle": "#E2E8F0",
    "surface-container-high": "#dce9ff",
    "on-error-container": "#93000a"
  },
  "borderRadius": {
    "DEFAULT": "0.25rem",
    "lg": "0.5rem",
    "xl": "0.75rem",
    "full": "9999px"
  },
  "spacing": {
    "space-md": "1rem",
    "margin": "1.25rem",
    "space-xs": "0.25rem",
    "space-xl": "2.5rem",
    "space-lg": "1.5rem",
    "gutter": "1.5rem",
    "margin-tablet": "2.5rem",
    "space-2xl": "4rem",
    "gutter-desktop": "2.5rem",
    "margin-desktop": "4rem",
    "space-3xl": "6rem",
    "space-sm": "0.5rem"
  },
  "fontFamily": {
    "headline-xl-mobile": [
      "Newsreader"
    ],
    "body-xl": [
      "Plus Jakarta Sans"
    ],
    "headline-2xl": [
      "Newsreader"
    ],
    "label-lg": [
      "Plus Jakarta Sans"
    ],
    "headline-lg": [
      "Newsreader"
    ],
    "label-md": [
      "Plus Jakarta Sans"
    ],
    "headline-sm": [
      "Plus Jakarta Sans"
    ],
    "body-md": [
      "Plus Jakarta Sans"
    ],
    "body-sm": [
      "Plus Jakarta Sans"
    ],
    "headline-md": [
      "Plus Jakarta Sans"
    ],
    "headline-xl": [
      "Newsreader"
    ],
    "headline-2xl-mobile": [
      "Newsreader"
    ],
    "label-sm": [
      "Plus Jakarta Sans"
    ]
  },
  "fontSize": {
    "headline-xl-mobile": [
      "1.75rem",
      {
        "lineHeight": "2.25rem",
        "letterSpacing": "-0.01em",
        "fontWeight": "600"
      }
    ],
    "body-xl": [
      "1.125rem",
      {
        "lineHeight": "1.875rem",
        "fontWeight": "400"
      }
    ],
    "headline-2xl": [
      "3.5rem",
      {
        "lineHeight": "4rem",
        "letterSpacing": "-0.02em",
        "fontWeight": "600"
      }
    ],
    "label-lg": [
      "0.875rem",
      {
        "lineHeight": "1.25rem",
        "letterSpacing": "0.01em",
        "fontWeight": "600"
      }
    ],
    "headline-lg": [
      "2rem",
      {
        "lineHeight": "2.5rem",
        "letterSpacing": "-0.01em",
        "fontWeight": "500"
      }
    ],
    "label-md": [
      "0.75rem",
      {
        "lineHeight": "1rem",
        "letterSpacing": "0.04em",
        "fontWeight": "700"
      }
    ],
    "headline-sm": [
      "1.25rem",
      {
        "lineHeight": "1.75rem",
        "fontWeight": "600"
      }
    ],
    "body-md": [
      "1rem",
      {
        "lineHeight": "1.625rem",
        "fontWeight": "400"
      }
    ],
    "body-sm": [
      "0.875rem",
      {
        "lineHeight": "1.375rem",
        "fontWeight": "400"
      }
    ],
    "headline-md": [
      "1.5rem",
      {
        "lineHeight": "2rem",
        "letterSpacing": "-0.01em",
        "fontWeight": "700"
      }
    ],
    "headline-xl": [
      "2.5rem",
      {
        "lineHeight": "3rem",
        "letterSpacing": "-0.015em",
        "fontWeight": "600"
      }
    ],
    "headline-2xl-mobile": [
      "2.25rem",
      {
        "lineHeight": "2.75rem",
        "letterSpacing": "-0.015em",
        "fontWeight": "600"
      }
    ],
    "label-sm": [
      "0.6875rem",
      {
        "lineHeight": "0.875rem",
        "letterSpacing": "0.05em",
        "fontWeight": "600"
      }
    ]
  }
};

export default {
  content: ['./index.html', './src/**/*.{js,jsx,html,txt}'],
  theme: { extend: theme },
  plugins: [],
};
