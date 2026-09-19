// ═══════════════════════════════════════════════════════════════
//  config.js — Home SAKALAVA · Nosy Be, Madagascar
//
//  ⚠ FICHIER GÉNÉRÉ AUTOMATIQUEMENT — NE PAS MODIFIER À LA MAIN.
//  Toute modification ici sera écrasée au prochain enregistrement
//  depuis le back-office.
//
//  Source des données : data/content.json
//  Régénérer          : php tools/build.php
//  Généré le          : 19/09/2026 à 03:43
// ═══════════════════════════════════════════════════════════════

const CONFIG = {
    "brand": {
        "name": "Home Sakalava",
        "logoText": "Sakalava",
        "tagline": "Maison d'hôtes les pieds dans le sable, à Nosy Be"
    },
    "theme": {
        "accent": "#187773",
        "dark": "#0a1a1a",
        "bg": "#f0ece5"
    },
    "settings": {
        "showPrices": false,
        "priceFallback": "Tarif sur demande"
    },
    "nav": {
        "links": [
            {
                "label": "Les chambres",
                "href": "#ideal"
            },
            {
                "label": "Le restaurant",
                "href": "#restaurant"
            },
            {
                "label": "Les activités",
                "href": "#activites"
            },
            {
                "label": "Accès",
                "href": "#acces"
            }
        ],
        "cta": {
            "label": "Nous écrire",
            "href": "#booking"
        }
    },
    "hero": {
        "image": {
            "src": "uploads/plage-coucher-soleil-59c20778-med.jpg",
            "webp": "uploads/plage-coucher-soleil-59c20778-med.webp",
            "srcset": {
                "webp": "uploads/plage-coucher-soleil-59c20778-thumb.webp 320w, uploads/plage-coucher-soleil-59c20778-small.webp 640w, uploads/plage-coucher-soleil-59c20778-med.webp 1200w",
                "jpg": "uploads/plage-coucher-soleil-59c20778-thumb.jpg 320w, uploads/plage-coucher-soleil-59c20778-small.jpg 640w, uploads/plage-coucher-soleil-59c20778-med.jpg 1200w"
            },
            "sizes": "100vw",
            "alt": "Coucher de soleil sur la plage à marée basse, un arbre isolé se reflétant dans l'eau, à quelques pas de Home Sakalava",
            "width": 1200,
            "height": 900,
            "eager": true
        },
        "gradient": "linear-gradient(145deg, #0a1a1a 0%, #1d3f3d 100%)",
        "logoName": "HOME SAKALAVA",
        "logoSub": "NOSY BE · MADAGASCAR",
        "bookLabel": "NOUS ÉCRIRE",
        "badge": "BORD DE PLAGE",
        "scrollHint": "FAITES DÉFILER",
        "headlineTitle": "LA PLAGE\nEST À DEUX PAS",
        "headlineSub": "AMPASIKELY · DZAMANDZAR",
        "features": [
            {
                "icon": "waves",
                "label": "Accès direct\nà la plage"
            },
            {
                "icon": "wifi",
                "label": "Wifi\ngratuit"
            },
            {
                "icon": "utensils",
                "label": "Restaurant\nsur place"
            },
            {
                "icon": "square-parking",
                "label": "Parking\ngratuit"
            }
        ],
        "prevRoom": "Standard Double",
        "nextRoom": "Comfort Triple"
    },
    "story": {
        "kicker": "Chez Boda et Bakoly",
        "text": "Home Sakalava, c'est cinq chambres au bord de l'eau, à Ampasikely, et deux hôtes qui vivent sur place. Boda et Bakoly vous ouvrent leur maison plutôt qu'une chambre d'hôtel : on prend le café ensemble le matin, on parle de la journée qui vient, et on vous dit où aller.",
        "stat": "Les murs sont peints de couleurs vives, les lits ont leur baldaquin et leur moustiquaire, le mobilier vient des artisans d'ici. Rien d'impersonnel, rien de standardisé. Et à quelques pas, le sable de la plage de Djamanjary.",
        "cta": {
            "label": "Découvrir la maison →",
            "href": "#amazing"
        }
    },
    "amazing": {
        "kicker": "La maison",
        "cta": {
            "label": "Voir les chambres",
            "href": "#ideal"
        },
        "items": [
            {
                "name": "Les chambres",
                "meta": "Lits à baldaquin · artisanat malgache",
                "image": {
                    "src": "uploads/chambre-lit-baldaquin-52286be5-med.jpg",
                    "webp": "uploads/chambre-lit-baldaquin-52286be5-med.webp",
                    "srcset": {
                        "webp": "uploads/chambre-lit-baldaquin-52286be5-thumb.webp 320w, uploads/chambre-lit-baldaquin-52286be5-small.webp 640w, uploads/chambre-lit-baldaquin-52286be5-med.webp 1280w",
                        "jpg": "uploads/chambre-lit-baldaquin-52286be5-thumb.jpg 320w, uploads/chambre-lit-baldaquin-52286be5-small.jpg 640w, uploads/chambre-lit-baldaquin-52286be5-med.jpg 1280w"
                    },
                    "sizes": "(max-width: 639px) 92vw, (max-width: 1023px) 48vw, 600px",
                    "alt": "Chambre avec lit à baldaquin et moustiquaire, mobilier en bois local, suspensions en vannerie et ouverture sur le jardin",
                    "width": 1280,
                    "height": 720,
                    "eager": false
                },
                "gradient": "linear-gradient(145deg, #1e3a5a 0%, #4a7aaa 100%)",
                "big": true
            },
            {
                "name": "Le jardin et la terrasse",
                "meta": "Solarium, parasols, salon commun",
                "image": null,
                "gradient": "linear-gradient(145deg, #2d5a3d 0%, #4a8a5e 100%)",
                "big": false
            },
            {
                "name": "Le restaurant",
                "meta": "Fruits de mer, midi et soir",
                "image": null,
                "gradient": "linear-gradient(145deg, #5a1e1e 0%, #aa5050 100%)",
                "big": false
            }
        ]
    },
    "ideal": {
        "kicker": "Cinq chambres, pas une de plus",
        "intro": "Toutes avec salle de bain privée, douche à l'italienne, entrée indépendante, armoire, linge de lit et serviettes fournis.",
        "shared": [
            "Salle de bain privée",
            "Douche à l'italienne",
            "Articles de toilette offerts",
            "Entrée indépendante",
            "Armoire",
            "Linge et serviettes fournis",
            "Moustiquaire",
            "Chambre non-fumeurs"
        ],
        "items": [
            {
                "id": "comfort-triple",
                "name": "Comfort Triple",
                "loc": "Vue mer · 30 m²",
                "tag": "La plus grande",
                "area": "30 m²",
                "desc": "La plus spacieuse de la maison, avec sa terrasse privée face à la mer. Climatisée, elle accueille confortablement trois personnes.",
                "price": "Tarif sur demande",
                "amenities": [
                    "Climatisation",
                    "Terrasse privée",
                    "Vue mer",
                    "Jusqu'à 3 personnes"
                ],
                "image": {
                    "src": "uploads/chambre-lit-baldaquin-52286be5-med.jpg",
                    "webp": "uploads/chambre-lit-baldaquin-52286be5-med.webp",
                    "srcset": {
                        "webp": "uploads/chambre-lit-baldaquin-52286be5-thumb.webp 320w, uploads/chambre-lit-baldaquin-52286be5-small.webp 640w, uploads/chambre-lit-baldaquin-52286be5-med.webp 1280w",
                        "jpg": "uploads/chambre-lit-baldaquin-52286be5-thumb.jpg 320w, uploads/chambre-lit-baldaquin-52286be5-small.jpg 640w, uploads/chambre-lit-baldaquin-52286be5-med.jpg 1280w"
                    },
                    "sizes": "(max-width: 639px) 92vw, (max-width: 1023px) 48vw, 600px",
                    "alt": "Chambre avec lit à baldaquin et moustiquaire, mobilier en bois local, suspensions en vannerie et ouverture sur le jardin",
                    "width": 1280,
                    "height": 720,
                    "eager": false
                },
                "gradient": "linear-gradient(145deg, #1a2a3a 0%, #3a6a90 100%)"
            },
            {
                "id": "standard-triple",
                "name": "Standard Triple",
                "loc": "Terrasse privée · 18 m²",
                "tag": "Pour trois",
                "area": "18 m²",
                "desc": "Une chambre pour trois avec sa terrasse et son balcon, pour prendre le petit-déjeuner dehors sans croiser personne.",
                "price": "Tarif sur demande",
                "amenities": [
                    "Terrasse privée",
                    "Balcon",
                    "Jusqu'à 3 personnes"
                ],
                "image": null,
                "gradient": "linear-gradient(145deg, #4a6a7a 0%, #6a8a9a 100%)"
            },
            {
                "id": "double-jardin",
                "name": "Double vue jardin",
                "loc": "Cour intérieure · 16 m²",
                "tag": "Avec baignoire",
                "area": "16 m²",
                "desc": "Ouverte sur le jardin et la cour intérieure, la seule chambre équipée d'une baignoire. Calme toute la journée.",
                "price": "Tarif sur demande",
                "amenities": [
                    "Baignoire",
                    "Vue jardin",
                    "Cour intérieure"
                ],
                "image": null,
                "gradient": "linear-gradient(145deg, #2a4a2f 0%, #5a8a60 100%)"
            },
            {
                "id": "double-terrasse",
                "name": "Double avec terrasse",
                "loc": "Climatisée · 14 m²",
                "tag": "Climatisée",
                "area": "14 m²",
                "desc": "Compacte et climatisée, avec terrasse privée et balcon. Le bon compromis pour deux.",
                "price": "Tarif sur demande",
                "amenities": [
                    "Climatisation",
                    "Terrasse privée",
                    "Balcon"
                ],
                "image": null,
                "gradient": "linear-gradient(145deg, #6a5840 0%, #9a8060 100%)"
            },
            {
                "id": "standard-double",
                "name": "Standard Double",
                "loc": "Pour deux · 13 m²",
                "tag": "L'essentiel",
                "area": "13 m²",
                "desc": "La plus simple : un lit, une salle de bain à soi, et la plage à deux pas. C'est souvent tout ce qu'il faut.",
                "price": "Tarif sur demande",
                "amenities": [
                    "Salle de bain privée",
                    "Entrée indépendante"
                ],
                "image": null,
                "gradient": "linear-gradient(145deg, #3a4a55 0%, #5a6a75 100%)"
            }
        ]
    },
    "trusted": {
        "title": "Une maison au bord de l'eau,\ntenue par ceux qui y vivent",
        "cta": {
            "label": "Nous écrire",
            "href": "#booking"
        },
        "amenities": [
            "Accès direct à la plage",
            "Wifi gratuit",
            "Parking privé gratuit",
            "Restaurant sur place",
            "Sécurité 24h/24",
            "Ménage quotidien"
        ],
        "items": [
            {
                "label": "Wifi gratuit dans toute la maison",
                "icon": "wifi",
                "supplement": false
            },
            {
                "label": "Parking privé gratuit sur place",
                "icon": "square-parking",
                "supplement": false
            },
            {
                "label": "Sécurité 24h/24",
                "icon": "shield-check",
                "supplement": false
            },
            {
                "label": "Ménage quotidien",
                "icon": "sparkles",
                "supplement": false
            },
            {
                "label": "Accès direct à la plage",
                "icon": "waves",
                "supplement": false
            },
            {
                "label": "Bureau d'excursions et conseils",
                "icon": "map",
                "supplement": false
            },
            {
                "label": "Change de devises",
                "icon": "banknote",
                "supplement": false
            },
            {
                "label": "Jeux de société",
                "icon": "dices",
                "supplement": false
            },
            {
                "label": "Navette aéroport",
                "icon": "plane",
                "supplement": true
            },
            {
                "label": "Location de voiture",
                "icon": "car",
                "supplement": true
            },
            {
                "label": "Location de vélos",
                "icon": "bike",
                "supplement": true
            },
            {
                "label": "Massages",
                "icon": "hand-heart",
                "supplement": true
            },
            {
                "label": "Blanchisserie et repassage",
                "icon": "shirt",
                "supplement": true
            },
            {
                "label": "Consigne à bagages",
                "icon": "luggage",
                "supplement": true
            },
            {
                "label": "Baby-sitting sur demande",
                "icon": "baby",
                "supplement": true
            }
        ],
        "note": "Les services marqués « supplément » sont facturés en plus du séjour.",
        "floatCard": {
            "title": "Une question avant de venir ? Boda et Bakoly répondent eux-mêmes, en français comme en anglais.",
            "email": "Adresse e-mail à renseigner"
        },
        "floatImages": [
            {
                "image": {
                    "src": "uploads/chambre-lit-baldaquin-52286be5-med.jpg",
                    "webp": "uploads/chambre-lit-baldaquin-52286be5-med.webp",
                    "srcset": {
                        "webp": "uploads/chambre-lit-baldaquin-52286be5-thumb.webp 320w, uploads/chambre-lit-baldaquin-52286be5-small.webp 640w, uploads/chambre-lit-baldaquin-52286be5-med.webp 1280w",
                        "jpg": "uploads/chambre-lit-baldaquin-52286be5-thumb.jpg 320w, uploads/chambre-lit-baldaquin-52286be5-small.jpg 640w, uploads/chambre-lit-baldaquin-52286be5-med.jpg 1280w"
                    },
                    "sizes": "(max-width: 639px) 45vw, 220px",
                    "alt": "Chambre avec lit à baldaquin et moustiquaire, mobilier en bois local, suspensions en vannerie et ouverture sur le jardin",
                    "width": 1280,
                    "height": 720,
                    "eager": false
                },
                "gradient": "linear-gradient(145deg, #0e2a44 0%, #1a4a7a 100%)",
                "label": "Les chambres"
            },
            {
                "image": null,
                "gradient": "linear-gradient(145deg, #0e3020 0%, #1a5a35 100%)",
                "label": "Le jardin"
            },
            {
                "image": {
                    "src": "uploads/plage-coucher-soleil-59c20778-med.jpg",
                    "webp": "uploads/plage-coucher-soleil-59c20778-med.webp",
                    "srcset": {
                        "webp": "uploads/plage-coucher-soleil-59c20778-thumb.webp 320w, uploads/plage-coucher-soleil-59c20778-small.webp 640w, uploads/plage-coucher-soleil-59c20778-med.webp 1200w",
                        "jpg": "uploads/plage-coucher-soleil-59c20778-thumb.jpg 320w, uploads/plage-coucher-soleil-59c20778-small.jpg 640w, uploads/plage-coucher-soleil-59c20778-med.jpg 1200w"
                    },
                    "sizes": "(max-width: 639px) 45vw, 220px",
                    "alt": "Coucher de soleil sur la plage à marée basse, un arbre isolé se reflétant dans l'eau, à quelques pas de Home Sakalava",
                    "width": 1200,
                    "height": 900,
                    "eager": false
                },
                "gradient": "linear-gradient(145deg, #2a3a1a 0%, #4a6030 100%)",
                "label": "La plage"
            },
            {
                "image": null,
                "gradient": "linear-gradient(145deg, #3a1a0e 0%, #6a3520 100%)",
                "label": "Le restaurant"
            }
        ]
    },
    "restaurant": {
        "kicker": "À table",
        "title": "Le restaurant de la maison",
        "text": "On mange ici midi et soir, dans une salle qui tient autant de la cuisine familiale que du restaurant. Fruits de mer surtout, et quelques plats européens. Les cocktails se prennent dehors.",
        "image": null,
        "gradient": "linear-gradient(145deg, #5a1e1e 0%, #aa5050 100%)",
        "items": [
            {
                "icon": "fish",
                "label": "Fruits de mer et cuisine européenne"
            },
            {
                "icon": "coffee",
                "label": "Petit-déjeuner continental ou à la carte"
            },
            {
                "icon": "bed",
                "label": "Petit-déjeuner servi en chambre sur demande"
            },
            {
                "icon": "salad",
                "label": "Options végétariennes et sans gluten"
            },
            {
                "icon": "utensils",
                "label": "Repas pour enfants (supplément)"
            },
            {
                "icon": "shopping-bag",
                "label": "Paniers repas à emporter"
            },
            {
                "icon": "concierge-bell",
                "label": "Service en chambre"
            },
            {
                "icon": "wine",
                "label": "Cocktails et café sur place"
            }
        ],
        "note": "Menus spéciaux sur demande, prévenez-nous à la réservation."
    },
    "activities": {
        "kicker": "Autour de la maison",
        "title": "Nosy Be, et Boda qui connaît l'île",
        "text": "Boda a passé des années à accompagner des voyageurs ici. Il sait quel bateau prendre, quel jour, et avec qui. Demandez-lui : c'est plus fiable que n'importe quel guide.",
        "items": [
            {
                "icon": "anchor",
                "label": "Plongée"
            },
            {
                "icon": "waves",
                "label": "Snorkeling"
            },
            {
                "icon": "fish",
                "label": "Pêche"
            },
            {
                "icon": "mountain",
                "label": "Randonnée"
            },
            {
                "icon": "bike",
                "label": "Vélo et tours à vélo"
            },
            {
                "icon": "rabbit",
                "label": "Équitation"
            },
            {
                "icon": "flag",
                "label": "Golf"
            },
            {
                "icon": "drum",
                "label": "Culture locale et soirées à thème"
            }
        ],
        "note": "La plupart de ces activités sont en supplément.",
        "distances": [
            {
                "label": "Plage de Djamanjary",
                "value": "à quelques pas"
            },
            {
                "label": "Mont Passot",
                "value": "17 km"
            },
            {
                "label": "Réserve de Lokobe",
                "value": "20 km"
            },
            {
                "label": "Aéroport de Fascene (NOS)",
                "value": "25 km"
            },
            {
                "label": "Hell-Ville, Nosy Komba, Nosy Sakatia, Nosy Tanikely",
                "value": "excursions au départ"
            }
        ]
    },
    "reputation": {
        "kicker": "Ce que disent nos hôtes",
        "title": "9,6 sur 10",
        "label": "Exceptionnel",
        "intro": "Note moyenne sur Booking, sur 75 avis de voyageurs. Nous n'affichons pas d'avis choisis à la main : voici le détail, tel quel.",
        "badge": "Excellent emplacement · Bord de plage",
        "scores": [
            {
                "label": "Propreté",
                "value": 10
            },
            {
                "label": "Personnel",
                "value": 10
            },
            {
                "label": "Wifi",
                "value": 10
            },
            {
                "label": "Confort",
                "value": 9.9
            },
            {
                "label": "Rapport qualité-prix",
                "value": 9.8
            },
            {
                "label": "Équipements",
                "value": 9.6
            },
            {
                "label": "Emplacement",
                "value": 9
            }
        ],
        "source": "Source : Booking.com · 75 avis"
    },
    "access": {
        "kicker": "Venir chez nous",
        "title": "Accès et contact",
        "text": "Nous sommes à Ampasikely, sur la commune de Dzamandzar, à 25 km de l'aéroport de Fascene. Dites-nous votre heure d'arrivée : nous pouvons organiser la navette.",
        "rows": [
            {
                "icon": "map-pin",
                "label": "Adresse",
                "value": "Ampasikely, 207 Dzamandzar, Nosy Be, région Diana, Madagascar",
                "href": ""
            },
            {
                "icon": "phone",
                "label": "Téléphone",
                "value": "[À renseigner]",
                "href": ""
            },
            {
                "icon": "mail",
                "label": "E-mail",
                "value": "[À renseigner]",
                "href": ""
            },
            {
                "icon": "navigation",
                "label": "Coordonnées GPS",
                "value": "[À renseigner]",
                "href": ""
            },
            {
                "icon": "log-in",
                "label": "Arrivée",
                "value": "12h00 – 23h00",
                "href": ""
            },
            {
                "icon": "log-out",
                "label": "Départ",
                "value": "11h00 – 12h00",
                "href": ""
            },
            {
                "icon": "languages",
                "label": "Langues parlées",
                "value": "Français et anglais",
                "href": ""
            },
            {
                "icon": "wallet",
                "label": "Paiement",
                "value": "Paiement sur place, en espèces",
                "href": ""
            }
        ],
        "facebook": "https://www.facebook.com/home.sakalava.nosybe"
    },
    "faq": {
        "kicker": "Questions fréquentes",
        "title": "Les questions\nqu'on nous pose",
        "intro": "Et si la vôtre n'y est pas, écrivez-nous.",
        "image": {
            "src": "uploads/plage-coucher-soleil-59c20778-med.jpg",
            "webp": "uploads/plage-coucher-soleil-59c20778-med.webp",
            "srcset": {
                "webp": "uploads/plage-coucher-soleil-59c20778-thumb.webp 320w, uploads/plage-coucher-soleil-59c20778-small.webp 640w, uploads/plage-coucher-soleil-59c20778-med.webp 1200w",
                "jpg": "uploads/plage-coucher-soleil-59c20778-thumb.jpg 320w, uploads/plage-coucher-soleil-59c20778-small.jpg 640w, uploads/plage-coucher-soleil-59c20778-med.jpg 1200w"
            },
            "sizes": "(max-width: 639px) 92vw, (max-width: 1023px) 48vw, 600px",
            "alt": "Coucher de soleil sur la plage à marée basse, un arbre isolé se reflétant dans l'eau, à quelques pas de Home Sakalava",
            "width": 1200,
            "height": 900,
            "eager": false
        },
        "items": [
            {
                "q": "Comment réserver une chambre ?",
                "a": "Écrivez-nous depuis le formulaire en bas de page ou passez par notre page Facebook. Nous vous répondons avec les disponibilités et le tarif. La réservation se confirme directement avec nous."
            },
            {
                "q": "À quelle heure puis-je arriver et repartir ?",
                "a": "L'arrivée se fait entre 12h00 et 23h00, le départ entre 11h00 et 12h00. Si votre vol arrive en dehors de ces horaires, prévenez-nous : on s'arrange."
            },
            {
                "q": "Comment se règle le séjour ?",
                "a": "Le paiement se fait sur place, en espèces. Nous ne demandons pas de caution et vous remettons une facture."
            },
            {
                "q": "Proposez-vous une navette depuis l'aéroport ?",
                "a": "Oui, à l'aller comme au retour. L'aéroport de Fascene est à 25 km. Ce service est payant : indiquez-nous votre numéro de vol et nous vous donnons le tarif."
            },
            {
                "q": "Y a-t-il un restaurant sur place ?",
                "a": "Oui, ouvert midi et soir. Fruits de mer et cuisine européenne, avec des options végétariennes et sans gluten. Le petit-déjeuner peut être servi en chambre si vous le demandez la veille."
            },
            {
                "q": "Venez-vous avec des enfants ou un animal ?",
                "a": "Les enfants de tout âge sont les bienvenus et nous avons des chambres familiales. Les animaux sont acceptés sur demande, avec un supplément possible. Un baby-sitting peut être organisé."
            },
            {
                "q": "Quelles langues parlez-vous ?",
                "a": "Français et anglais. Boda et Bakoly vous accueillent eux-mêmes."
            },
            {
                "q": "La maison est-elle accessible en fauteuil ?",
                "a": "La maison est accessible aux personnes à mobilité réduite. Écrivez-nous avant de réserver pour que nous vous orientions vers la chambre la plus adaptée."
            }
        ]
    },
    "booking": {
        "kicker": "Demande de réservation",
        "title": "Écrivez-nous",
        "subtitle": "Nosy Be · Madagascar",
        "intro": "Remplissez ce formulaire, il prépare un message pour Boda et Bakoly. Aucun paiement n'est demandé ici : nous vous répondons d'abord avec les disponibilités et le tarif.",
        "infoCard": [
            {
                "label": "Arrivée",
                "value": "12h00 – 23h00"
            },
            {
                "label": "Départ",
                "value": "11h00 – 12h00"
            },
            {
                "label": "Langues",
                "value": "Français et anglais"
            },
            {
                "label": "Paiement",
                "value": "Paiement sur place, en espèces"
            },
            {
                "label": "Téléphone",
                "value": "[À renseigner]"
            },
            {
                "label": "E-mail",
                "value": "[À renseigner]"
            }
        ],
        "rooms": [
            "Comfort Triple",
            "Standard Triple",
            "Double vue jardin",
            "Double avec terrasse",
            "Standard Double"
        ],
        "mailto": "",
        "facebook": "https://www.facebook.com/home.sakalava.nosybe",
        "labels": {
            "firstName": "Prénom",
            "lastName": "Nom",
            "email": "E-mail",
            "phone": "Téléphone",
            "checkIn": "Date d'arrivée",
            "checkOut": "Date de départ",
            "roomType": "Chambre souhaitée",
            "guests": "Voyageurs",
            "message": "Votre message",
            "submit": "Préparer ma demande",
            "note": "Ce bouton ouvre votre messagerie avec le message déjà rédigé. Rien n'est envoyé automatiquement, vous relisez avant.",
            "noEmail": "L'adresse e-mail de la maison n'est pas encore en ligne. En attendant, écrivez-nous sur Facebook :",
            "fbLink": "Ouvrir la page Facebook",
            "successTitle": "Votre message est prêt",
            "successText": "Votre messagerie vient de s'ouvrir avec la demande pré-remplie. Relisez-la et envoyez-la — nous répondons sous quelques jours.",
            "resetBtn": "Recommencer",
            "ph": {
                "firstName": "Prénom",
                "lastName": "Nom",
                "email": "vous@exemple.com",
                "phone": "Indicatif compris",
                "message": "Nombre de nuits, heure d'arrivée, navette aéroport, régime alimentaire…"
            }
        }
    },
    "footer": {
        "cta": {
            "title": "La plage vous attend\nà deux pas",
            "button": "Nous écrire",
            "href": "#booking",
            "image": {
                "src": "uploads/plage-coucher-soleil-59c20778-med.jpg",
                "webp": "uploads/plage-coucher-soleil-59c20778-med.webp",
                "srcset": {
                    "webp": "uploads/plage-coucher-soleil-59c20778-thumb.webp 320w, uploads/plage-coucher-soleil-59c20778-small.webp 640w, uploads/plage-coucher-soleil-59c20778-med.webp 1200w",
                    "jpg": "uploads/plage-coucher-soleil-59c20778-thumb.jpg 320w, uploads/plage-coucher-soleil-59c20778-small.jpg 640w, uploads/plage-coucher-soleil-59c20778-med.jpg 1200w"
                },
                "sizes": "(max-width: 639px) 92vw, (max-width: 1023px) 48vw, 600px",
                "alt": "Coucher de soleil sur la plage à marée basse, un arbre isolé se reflétant dans l'eau, à quelques pas de Home Sakalava",
                "width": 1200,
                "height": 900,
                "eager": false
            },
            "gradient": "linear-gradient(145deg, #0c1015 0%, #152030 100%)"
        },
        "wordmark": "Sakalava",
        "address": [
            "Home Sakalava",
            "Ampasikely",
            "207 Dzamandzar",
            "Nosy Be, région Diana",
            "Madagascar"
        ],
        "social": [
            {
                "label": "Facebook",
                "href": "https://www.facebook.com/home.sakalava.nosybe"
            }
        ],
        "legal": [
            "Mentions légales",
            "Politique de confidentialité"
        ],
        "copyright": "© 2026 Home Sakalava. Tous droits réservés."
    }
};
