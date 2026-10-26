<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Article;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::first();
        if (!$author) {
            $author = User::create([
                'name' => 'Super Administrateur VoiCI-EMI',
                'email' => 'sa@voici-emi.bj',
                'password' => bcrypt('Admin12345!'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }

        $news = [
            [
                'title' => 'Cotonou : Déploiement d\'une caravane de médiateurs EMI dans les maisons des jeunes',
                'content' => "Le Ministère de l'Économie Numérique et de la Digitalisation, en synergie avec VoiCI-EMI et les associations de jeunesse, a officiellement lancé ce matin à Cotonou un cycle de 50 ateliers de formation à l'esprit critique. Destiné aux délégués scolaires et aux leaders d'associations de quartier, ce dispositif vise à déconstruire les arnaques financières circulant sur WhatsApp et à enseigner la méthode de vérification d'images inversées. Plus de 1 200 jeunes ont participé à cette première session.",
                'summary_falc' => "Des formateurs sont venus à Cotonou pour apprendre aux jeunes comment repérer les faux messages sur leur téléphone.\nLes jeunes apprennent à vérifier les photos et à ne pas partager des mensonges sur WhatsApp.",
                'reliability_score' => 'eleve',
                'is_fact_checked' => true,
                'published_at' => now()->subHours(2),
            ],
            [
                'title' => 'Alerte WhatsApp : Démenti formel sur un prétendu recrutement sans concours à la douane béninoise',
                'content' => "Un message audio viral massivement transféré sur les réseaux sociaux prétend que la Direction Générale des Douanes du Bénin recrute sans condition de diplôme 1 500 agents contre un versement de 25 000 FCFA par Mobile Money. La cellule de communication du Ministère de l'Économie et des Finances et l'ODEM confirment qu'il s'agit d'une escroquerie bien rodée. Aucun recrutement n'est en cours sans arrêté publié au Journal Officiel.",
                'summary_falc' => "Attention : Un faux message dit que la douane recrute des personnes si elles envoient de l'argent par téléphone.\nC'est un mensonge d'escrocs.\nL'État ne demande jamais d'argent pour un recrutement.",
                'reliability_score' => 'faible',
                'is_fact_checked' => true,
                'published_at' => now()->subHours(5),
            ],
            [
                'title' => 'Parakou : Production de chroniques radiophoniques en langues Bariba et Dendi',
                'content' => "Les radios communautaires du Septentrion (Borgou et Alibori) diffusent désormais trois fois par jour des capsules éducatives de deux minutes traduisant les verdicts de vérification dans les langues du terroir. Cette initiative permet d'informer les commerçantes et producteurs ruraux des techniques d'escroquerie par faux SMS bancaires sans barrière de langue.",
                'summary_falc' => "À Parakou, les radios locales expliquent à la population en langue Bariba et Dendi comment éviter les arnaques sur téléphone.\nTout le monde peut écouter et comprendre facilement.",
                'reliability_score' => 'eleve',
                'is_fact_checked' => true,
                'published_at' => now()->subHours(8),
            ],
            [
                'title' => 'Afrique de l\'Ouest : Concertation régionale contre la propagation des deepfakes électoraux',
                'content' => "Des rédactions de vérification du Bénin, du Togo, de Côte d'Ivoire et du Sénégal ont signé une convention de mutualisation de leurs cellules d'investigation numérique. Ce réseau transfrontalier permet d'analyser en moins de 15 minutes les vidéos manipulées par intelligence artificielle lors des scrutins électoraux régionaux.",
                'summary_falc' => "Les journalistes de plusieurs pays d'Afrique de l'Ouest travaillent ensemble pour trouver rapidement les fausses vidéos créées par ordinateur.",
                'reliability_score' => 'eleve',
                'is_fact_checked' => true,
                'published_at' => now()->subDay(),
            ],
            [
                'title' => 'Santé Publique : L\'OMS met en garde contre les fausses infusions censées guérir l\'hypertension',
                'content' => "Des publications sponsorisées sur TikTok font la promotion d'une décoction de plantes locales affirmant guérir définitivement l'hypertension artérielle. La Société Béninoise de Cardiologie et l'OMS rappellent que l'hypertension nécessite un suivi médical strict et que l'abandon des traitements prescrits est une cause majeure d'AVC.",
                'summary_falc' => "Faux remède : Des vidéos disent qu'une tisane guérit totalement l'hypertension.\nLes médecins rappellent qu'il ne faut jamais arrêter ses médicaments sans voir un docteur.",
                'reliability_score' => 'faible',
                'is_fact_checked' => true,
                'published_at' => now()->subDays(2),
            ]
        ];

        foreach ($news as $item) {
            Article::create([
                'title' => $item['title'],
                'slug' => Str::slug($item['title']) . '-' . uniqid(),
                'content' => $item['content'],
                'author_id' => $author->id,
                'status' => 'published',
                'published_at' => $item['published_at'],
                'reliability_score' => $item['reliability_score'],
                'is_fact_checked' => $item['is_fact_checked'],
                'summary_falc' => $item['summary_falc'],
            ]);
        }
    }
}
