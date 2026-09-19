// ═══════════════════════════════════════════════════════════════
//  config.js — Le Port Hôtel · Fort Dauphin, Madagascar
//  C'est LE seul fichier à modifier pour changer contenu/couleurs.
//  Les images utilisent picsum.photos (seed = résultat toujours identique).
// ═══════════════════════════════════════════════════════════════

const CONFIG = {

  brand: {
    name:     "Talinjoo Hotel",
    logoText: "Talinjoo",
  },

  theme: {
    accent: "#1d8f8a",
    dark:   "#0a1a1a",
    bg:     "#f0ece5",
  },

  nav: {
    links: [
      { label: "Nos Chambres",   href: "#ideal"   },
      { label: "Nos Atouts",     href: "#trusted" },
      { label: "Notre Histoire", href: "#story"   },
    ],
    cta: { label: "Réserver", href: "#booking" },
  },

  hero: {
    // Conservés pour compatibilité (animations, etc.)
    image:    "hero1.webp",
    // ─── Nouveau design glassmorphism ───────────────
    logoName:      "TALINJOO HOTEL",
    logoSub:       "FORT DAUPHIN · MADAGASCAR",
    bookLabel:     "RÉSERVER",
    badge:         "VUE SUR MER",
    scrollHint:    "DÉFILER POUR DÉCOUVRIR",
    headlineTitle: "SUITE PANORAMA",
    headlineSub:   "VUE MER · FORT DAUPHIN",
    features: [
      { icon: "bed-double", label: "Chambres\nConfort"      },
      { icon: "wifi",       label: "WiFi\nGratuit"          },
      { icon: "utensils",   label: "Restaurant\nTerrasse"   },
      { icon: "eye",        label: "Belle\nVue"             },
    ],
    prevRoom: "Chambre Standard",
    nextRoom: "Chambre Confort",
  },

  story: {
    kicker: "Notre Histoire",
    text:   "Niché au cœur de Fort Dauphin, Talinjoo Hotel vous offre un havre de confort et de sérénité, à quelques pas du port et de toutes les commodités de la ville.",
    stat:   "Un établissement familial qui accueille ses hôtes avec chaleur et authenticité. WiFi gratuit, terrasse panoramique et restaurant sur place pour un séjour sans compromis.",
    cta:    { label: "En savoir plus →", href: "#amazing" },
  },

  amazing: {
    kicker: "Nos espaces phares",
    cta:    { label: "Voir les chambres", href: "#ideal" },
    items: [
      {
        name:     "La Terrasse",
        meta:     "Vue panoramique · Fort Dauphin",
        image:    "Talinjoo-Hotel-21.webp",
        gradient: "linear-gradient(145deg, #2d5a3d 0%, #4a8a5e 100%)",
        big:      true,
      },
      {
        name:     "Nos Chambres",
        meta:     "Confort & élégance",
        image:    "DSC7896-681x1024.jpg",
        gradient: "linear-gradient(145deg, #1e3a5a 0%, #4a7aaa 100%)",
      },
      {
        name:     "Le Restaurant",
        meta:     "Cuisine locale & internationale",
        image:    "talinjoo-hotel-fort-dauphin-hotel-restaurant-madagascar-341476-kyyzhy.webp",
        gradient: "linear-gradient(145deg, #5a1e1e 0%, #aa5050 100%)",
      },
    ],
  },

  ideal: {
    kicker: "Découvrez nos chambres",
    items: [
      {
        name:     "Chambre Standard",
        loc:      "Vue sur cour · Fort Dauphin",
        tag:      "Confort",
        image:    "images (5).jfif",
        gradient: "linear-gradient(145deg, #4a6a7a 0%, #6a8a9a 100%)",
      },
      {
        name:     "Chambre Confort",
        loc:      "Vue sur jardin · Fort Dauphin",
        tag:      "Populaire",
        image:    "images (6).jfif",
        gradient: "linear-gradient(145deg, #6a5840 0%, #9a8060 100%)",
      },
      {
        name:     "Chambre Supérieure",
        loc:      "Vue sur ville · Fort Dauphin",
        tag:      "Calme",
        image:    "DSC7896-681x1024.jpg",
        gradient: "linear-gradient(145deg, #3a4a55 0%, #5a6a75 100%)",
      },
      {
        name:     "Suite Panoramique",
        loc:      "Vue mer · Fort Dauphin",
        tag:      "★ Best-seller",
        image:    "Talinjoo-Hotel-21.webp",
        gradient: "linear-gradient(145deg, #1a2a3a 0%, #3a6a90 100%)",
      },
      {
        name:     "Chambre Familiale",
        loc:      "Grand espace · Fort Dauphin",
        tag:      "Famille",
        image:    "Talinjoo-Hotel-33.webp",
        gradient: "linear-gradient(145deg, #4a5030 0%, #7a8060 100%)",
      },
      {
        name:     "Chambre Vue Port",
        loc:      "Face au port · Fort Dauphin",
        tag:      "Premium",
        image:    "piscine-mer-talinjoo-hotel-tolanaro-fort-dauphin.jpg",
        gradient: "linear-gradient(145deg, #1a3a5a 0%, #3a7ab0 100%)",
      },
    ],
  },

  trusted: {
    title: "Un confort sans compromis,\nune expérience inoubliable",
    cta:   { label: "Réserver maintenant", href: "#booking" },
    amenities: [
      "WiFi Gratuit",
      "Terrasse Panoramique",
      "Restaurant sur place",
      "Belle Vue",
      "Centre-ville",
      "Chambres Confortables",
    ],
    floatCard: {
      title: "Profitez du WiFi gratuit et d'une restauration sur place tout au long de votre séjour à Fort Dauphin.",
      email: "contact@talinjoohotel.mg",
    },
    floatImages: [
      { image: "DSC7896-681x1024.jpg",                                                          gradient: "linear-gradient(145deg, #0e2a44 0%, #1a4a7a 100%)", label: "WiFi Gratuit"  },
      { image: "Talinjoo-Hotel-21.webp",                                                        gradient: "linear-gradient(145deg, #0e3020 0%, #1a5a35 100%)", label: "Belle Vue"     },
      { image: "Talinjoo-Hotel-33.webp",                                                        gradient: "linear-gradient(145deg, #2a3a1a 0%, #4a6030 100%)", label: "Terrasse"      },
      { image: "talinjoo-hotel-fort-dauphin-hotel-restaurant-madagascar-341476-kyyzhy.webp",    gradient: "linear-gradient(145deg, #3a1a0e 0%, #6a3520 100%)", label: "Restaurant"    },
    ],
  },

  testimonial: {
    kicker: "Avis clients",
    intro:  "Voici ce que nos hôtes disent de leur séjour. Des moments authentiques, des expériences réelles.",
    title:  "Un accueil chaleureux et des chambres impeccables !",
    body:   "La chambre était propre, confortable et la vue magnifique. Le personnel a été aux petits soins tout au long de notre séjour. Je recommande vivement Talinjoo Hotel à tous ceux qui visitent Fort Dauphin !",
    author: {
      name:     "Marie Dupont",
      role:     "Voyageuse · ★★★★★",
      image:    "https://picsum.photos/seed/woman-portrait/80/80",
      gradient: "linear-gradient(145deg, #c49a6a 0%, #a07850 100%)",
    },
  },

  faq: {
    kicker:        "Questions fréquentes",
    title:         "Vous avez des questions ?\nNous avons les réponses.",
    intro:         "Tout ce que vous devez savoir avant votre séjour à Fort Dauphin.",
    image:         "piscine-mer-talinjoo-hotel-tolanaro-fort-dauphin.jpg",
    imageGradient: "linear-gradient(145deg, #2a4a5a 0%, #6a9aaa 100%)",
    items: [
      {
        q: "Comment réserver une chambre ?",
        a: "Contactez-nous par téléphone au +261 32 XX XXX XX ou par email à contact@leporthotel.mg. Nous vous confirmons la disponibilité et le tarif dans les plus brefs délais.",
      },
      {
        q: "À quelle heure est le check-in et le check-out ?",
        a: "Le check-in est disponible à partir de 14h00. Le check-out doit être effectué avant 12h00. Des arrangements pour un check-in anticipé ou un check-out tardif sont possibles selon disponibilité.",
      },
      {
        q: "Le WiFi est-il gratuit ?",
        a: "Oui, le WiFi haut débit est entièrement gratuit dans toutes les chambres ainsi que dans les espaces communs : terrasse, restaurant et réception.",
      },
      {
        q: "Proposez-vous un service de restauration ?",
        a: "Notre restaurant est ouvert tous les jours pour le petit-déjeuner, le déjeuner et le dîner. Cuisine locale malgache et internationale, préparée avec des produits frais.",
      },
      {
        q: "L'hôtel est-il proche du centre-ville ?",
        a: "Oui, Le Port Hôtel est situé au cœur du centre-ville de Fort Dauphin (Tôlanaro), à quelques minutes à pied du port, des commerces et des principaux points d'intérêt.",
      },
      {
        q: "Acceptez-vous les cartes bancaires ?",
        a: "Nous acceptons Visa et Mastercard, ainsi que les paiements en espèces en ariary malgache ou en euros. Le paiement mobile (MVola, Airtel Money) est également accepté.",
      },
    ],
  },

  booking: {
    kicker:   "Réservation",
    title:    "Réservez\nvotre séjour",
    subtitle: "Fort Dauphin · Madagascar",
    intro:    "Complétez le formulaire et notre équipe vous confirmera votre réservation sous 24h. Aucun paiement requis à cette étape.",
    infoCard: [
      { label: "Check-in",  value: "À partir de 14h00"        },
      { label: "Check-out", value: "Avant 12h00"              },
      { label: "Tél",       value: "+261 32 XX XXX XX"        },
      { label: "Email",     value: "contact@talinjoohotel.mg" },
    ],
    rooms: [
      "Chambre Standard",
      "Chambre Confort",
      "Chambre Supérieure",
      "Suite Panoramique",
      "Chambre Familiale",
      "Chambre Vue Port",
    ],
    labels: {
      firstName:    "Prénom",
      lastName:     "Nom",
      email:        "E-mail",
      phone:        "Téléphone",
      checkIn:      "Date d'arrivée",
      checkOut:     "Date de départ",
      roomType:     "Type de chambre",
      guests:       "Voyageurs",
      message:      "Demandes particulières",
      submit:       "Envoyer la demande",
      note:         "Réponse garantie sous 24h · Annulation gratuite jusqu'à 48h avant l'arrivée",
      successTitle: "Demande envoyée !",
      successText:  "Notre équipe vous contactera dans les 24h pour confirmer votre réservation.",
      resetBtn:     "Nouvelle réservation",
      ph: {
        firstName: "Jean",
        lastName:  "Dupont",
        email:     "jean@exemple.com",
        phone:     "+33 6 00 00 00 00",
        message:   "Vue mer souhaitée, occasion spéciale, allergies…",
      },
    },
  },

  footer: {
    cta: {
      title:    "Vivez Fort Dauphin\nComme il se doit",
      button:   "Réserver maintenant",
      href:     "#booking",
      image:    "piscine-mer-talinjoo-hotel-tolanaro-fort-dauphin.jpg",
      gradient: "linear-gradient(145deg, #0c1015 0%, #152030 100%)",
    },
    wordmark: "Talinjoo",
    address: [
      "Talinjoo Hotel",
      "Centre-ville",
      "Fort Dauphin (Tôlanaro) 614",
      "Madagascar",
      "contact@talinjoohotel.mg",
      "+261 32 XX XXX XX",
    ],
    social: [
      { label: "Facebook",    href: "#" },
      { label: "Instagram",   href: "#" },
      { label: "TripAdvisor", href: "#" },
    ],
    legal: [
      "Mentions légales",
      "Politique de confidentialité",
      "Conditions d'utilisation",
    ],
    copyright: "©2026 Talinjoo Hotel. Tous droits réservés.",
  },

};
