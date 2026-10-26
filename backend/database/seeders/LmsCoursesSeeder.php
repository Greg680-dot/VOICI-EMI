<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Course;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use App\Models\CourseEnrollment;
use App\Models\CourseProgress;
use App\Models\Certificate;

class LmsCoursesSeeder extends Seeder
{
    public function run(): void
    {
        // Find formateur and super admin
        $trainer = User::where('email', 'formateur@voici-emi.bj')->first();
        $admin = User::where('email', 'sa@voici-emi.bj')->first();
        $authorId = $trainer ? $trainer->id : ($admin ? $admin->id : 1);

        // Delete test courses with 0 modules to keep catalog clean
        Course::whereIn('title', ['Test Cours', 'Titre Test'])->delete();

        // Update Course 1 if exists, or create
        $course1 = Course::updateOrCreate(
            ['id' => 1],
            [
                'title' => 'Déjouer les pièges des fausses informations en ligne (Fact-Checking & Vérification)',
                'description' => 'Un cursus pratique pour repérer la manipulation sur les réseaux sociaux, vérifier les images et identifier les biais de confirmation.',
                'author_id' => $authorId,
                'status' => 'published',
                'category' => 'Fact-Checking & Vérification',
                'level' => 'Débutant',
                'duration' => '3 heures',
                'icon' => '🔍',
                'badge_color' => 'emerald',
            ]
        );

        $ch1 = Chapter::firstOrCreate(
            ['course_id' => $course1->id, 'order' => 1],
            ['title' => 'Module 1 : Anatomie de la désinformation sur WhatsApp']
        );

        $l1 = Lesson::firstOrCreate(
            ['chapter_id' => $ch1->id, 'order' => 1],
            [
                'title' => 'Les ressorts émotionnels des messages viraux',
                'content_type' => 'text',
                'content' => "En Afrique de l'Ouest, WhatsApp constitue le premier canal de transmission de l'information de proximité. Les infox y exploitent trois leviers psychologiques majeurs : l'urgence feinte (« Transférez immédiatement avant minuit »), la peur collective (fausses alertes sanitaires ou empoisonnements imaginaires), et l'appel à la solidarité communautaire ou religieuse.\n\nAvant de transférer un message à vos proches, appliquez systématiquement la règle des trois questions : Qui est l'émetteur vérifiable ? Où est la source primaire citée ? Quel est l'intérêt de celui qui partage ce texte ?",
                'content_simplified' => "Sur WhatsApp, beaucoup de faux messages circulent.\nLes personnes qui inventent ces fausses nouvelles utilisent souvent la peur ou l'urgence pour vous pousser à partager sans réfléchir.\n\nAvant d'envoyer un message à vos amis ou à votre famille :\n1. Regardez bien qui a écrit le message d'origine.\n2. Vérifiez si les radios, journaux ou ministères officiels en parlent aussi.\n3. Si vous avez un doute, ne partagez pas le message.",
                'transcription' => 'Transcription audio disponible pour les apprenants en situation de handicap auditif.',
            ]
        );

        $l2 = Lesson::firstOrCreate(
            ['chapter_id' => $ch1->id, 'order' => 2],
            [
                'title' => 'La recherche d\'images inversée',
                'content_type' => 'pdf',
                'content' => "Une technique courante de manipulation consiste à réutiliser une photo ancienne prise lors d'une catastrophe ou d'une manifestation dans un autre pays, et de prétendre qu'elle vient de se produire à Cotonou ou à Parakou.\n\nConsultez le guide officiel téléchargeable en format PDF ci-dessous pour maîtriser les moteurs TinEye, Google Lens et InVID WeVerify.",
                'content_simplified' => "Parfois, une photo ancienne est présentée comme un événement qui vient de se passer aujourd'hui au Bénin.\n\nVous pouvez utiliser un outil gratuit sur votre téléphone pour retrouver où et quand cette photo est apparue pour la première fois.",
                'video_url' => 'https://example.com/guide-verification-images-benin.pdf',
            ]
        );

        // Course 2
        $course2 = Course::updateOrCreate(
            ['id' => 2],
            [
                'title' => 'Protection numérique et détection des arnaques Mobile Money & Phishing',
                'description' => 'Comprendre les arnaques financières par SMS, les faux appels bancaires et sécuriser ses transactions quotidiennes au Bénin.',
                'author_id' => $authorId,
                'status' => 'published',
                'category' => 'Hygiène & Sécurité Numérique',
                'level' => 'Tous niveaux',
                'duration' => '2h 15min',
                'icon' => '🛡️',
                'badge_color' => 'blue',
            ]
        );

        $ch2 = Chapter::firstOrCreate(
            ['course_id' => $course2->id, 'order' => 1],
            ['title' => 'Module 1 : Les techniques d\'ingénierie sociale & SMS frauduleux']
        );

        Lesson::firstOrCreate(
            ['chapter_id' => $ch2->id, 'order' => 1],
            [
                'title' => 'Les faux messages de confirmation de dépôt',
                'content_type' => 'image',
                'content' => "L'escroc vous envoie un faux SMS imitant le format d'un opérateur de téléphonie (MTN Mobile Money, Moov Money), puis vous appelle affolé en affirmant qu'il s'est trompé de numéro et vous supplie de lui renvoyer la somme.\n\nNe vous fiez jamais au SMS reçu : composez vous-même le code secret USSD de votre opérateur pour consulter votre solde réel.",
                'content_simplified' => "Un escroc peut vous envoyer un faux SMS qui ressemble à un message de transfert d'argent.\nVérifiez toujours votre solde réel en tapant votre code secret avant de répondre.",
                'video_url' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?auto=format&fit=crop&w=1200&q=80',
            ]
        );

        // Course 6 (Renamed / Ensured Course 6)
        $course6 = Course::updateOrCreate(
            ['id' => 6],
            [
                'title' => 'Détection Avancée des Rumeurs WhatsApp et Médias Sociaux au Bénin',
                'description' => 'Maîtrisez les techniques de fact-checking communautaire et découvrez comment neutraliser les infox avec des explications vocales en langues nationales.',
                'author_id' => $authorId,
                'status' => 'published',
                'category' => 'Éducation aux Médias',
                'level' => 'Intermédiaire',
                'duration' => '3h 30min',
                'icon' => '📱',
                'badge_color' => 'purple',
            ]
        );

        $ch6 = Chapter::firstOrCreate(
            ['course_id' => $course6->id, 'order' => 1],
            ['title' => 'Module 1 : Traçage des rumeurs vocales et messages en chaîne']
        );

        Lesson::firstOrCreate(
            ['chapter_id' => $ch6->id, 'order' => 1],
            [
                'title' => 'Guide méthodologique de vérification des vocaux WhatsApp',
                'content_type' => 'pdf',
                'content' => "Guide pratique édité par la rédaction de VoiCI-EMI récapitulant les 5 étapes d'investigation sur les notes vocales non sourcées circulant dans les groupes communautaires.",
                'content_simplified' => "Guide simple pour savoir comment vérifier une note vocale reçue dans un groupe WhatsApp.",
                'video_url' => 'https://example.com/guide-vocaux-emi-benin.pdf',
            ]
        );

        Lesson::firstOrCreate(
            ['chapter_id' => $ch6->id, 'order' => 2],
            [
                'title' => 'Explication vocale en Fɔngbe : Les dangers des rumeurs sanitaires',
                'content_type' => 'audio',
                'content' => "Transcription et commentaire audio complet en langue nationale Fɔngbe expliquant comment les rumeurs médicales peuvent causer des drames humains et comment vérifier auprès des centres de santé.",
                'content_simplified' => "Audio en langue locale pour expliquer comment déceler les faux remèdes.",
                'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
                'transcription' => 'Nu e ji a na gban xó adingban tɔn lɛ le do WhatsApp ji (Fɔngbe). Mi ma yawu se bo gba kpe o.',
            ]
        );

        // Course 7: Journalisme Citoyen & Enquête d'Intérêt Public
        $course7 = Course::updateOrCreate(
            ['title' => 'Journalisme Citoyen & Enquête d\'Intérêt Public'],
            [
                'description' => 'Apprenez à enquêter sur le terrain en toute sécurité, à protéger vos sources citoyennes et à rédiger des alertes d\'information factuelles conformes à la déontologie.',
                'author_id' => $authorId,
                'status' => 'published',
                'category' => 'Journalisme & Enquête',
                'level' => 'Avancé',
                'duration' => '4 heures',
                'icon' => '✍️',
                'badge_color' => 'amber',
            ]
        );

        $ch7_1 = Chapter::firstOrCreate(
            ['course_id' => $course7->id, 'order' => 1],
            ['title' => 'Module 1 : Méthodologie du reportage et sécurité du citoyen enquêteur']
        );

        Lesson::firstOrCreate(
            ['chapter_id' => $ch7_1->id, 'order' => 1],
            [
                'title' => 'Principes éthiques et protection des sources d\'information',
                'content_type' => 'text',
                'content' => "Le journalisme citoyen n'est pas une simple diffusion d'opinions personnelles : il repose sur la vérification scrupuleuse des faits, la contradiction des sources et le respect de la présomption d'innocence.\n\nNe révélez jamais le numéro de téléphone, le lieu précis de résidence ou l'identité d'un lanceur d'alerte sans son consentement éclairé.",
                'content_simplified' => "Quand vous partagez une information sur votre ville ou village, vérifiez toujours plusieurs témoins avant de publier.",
            ]
        );

        Lesson::firstOrCreate(
            ['chapter_id' => $ch7_1->id, 'order' => 2],
            [
                'title' => 'Fiche Pratique PDF : Checklist de l\'enquête de proximité',
                'content_type' => 'pdf',
                'content' => "Document officiel comprenant la grille d'auto-évaluation en 10 points avant toute publication citoyenne.",
                'video_url' => 'https://example.com/checklist-journaliste-citoyen.pdf',
            ]
        );

        // Course 8: Intelligence Artificielle, Deepfakes et Manipulation Visuelle
        $course8 = Course::updateOrCreate(
            ['title' => 'Intelligence Artificielle, Deepfakes et Manipulation Visuelle'],
            [
                'description' => 'Comprendre la génération d\'hypertruquages (Deepfakes vidéo et clonage vocal), identifier les artefacts visuels et déjouer les manipulations numériques sophistiquées.',
                'author_id' => $authorId,
                'status' => 'published',
                'category' => 'Technologies & IA',
                'level' => 'Intermédiaire',
                'duration' => '2h 45min',
                'icon' => '🤖',
                'badge_color' => 'rose',
            ]
        );

        $ch8_1 = Chapter::firstOrCreate(
            ['course_id' => $course8->id, 'order' => 1],
            ['title' => 'Module 1 : Anatomie des Deepfakes et clonage de voix']
        );

        Lesson::firstOrCreate(
            ['chapter_id' => $ch8_1->id, 'order' => 1],
            [
                'title' => 'Vidéo pédagogique : Comment repérer une fausse vidéo créée par IA',
                'content_type' => 'video',
                'content' => "Tutoriel vidéo décortiquant les incohérences de reflets dans les yeux, les anomalies de clignement des paupières, les déformations au niveau des dents et les transitions de contour.",
                'video_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
            ]
        );

        Lesson::firstOrCreate(
            ['chapter_id' => $ch8_1->id, 'order' => 2],
            [
                'title' => 'Infographie comparative : Photo réelle vs Photo générée par Midjourney',
                'content_type' => 'image',
                'content' => "Schéma d'analyse comparative haute résolution mettant en évidence les textures de peau trop lisses et les artefacts des arrière-plans générés par IA.",
                'video_url' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1200&q=80',
            ]
        );

        // Course 9: Code du Numérique au Bénin & Lutte contre le Cyberharcèlement
        $course9 = Course::updateOrCreate(
            ['title' => 'Code du Numérique au Bénin & Lutte contre le Cyberharcèlement'],
            [
                'description' => 'Connaître vos droits et obligations en ligne, les sanctions prévues par le Code du Numérique béninois et les réflexes pour se protéger du lynchage numérique.',
                'author_id' => $authorId,
                'status' => 'published',
                'category' => 'Droit & Citoyenneté',
                'level' => 'Tous niveaux',
                'duration' => '2 heures',
                'icon' => '⚖️',
                'badge_color' => 'indigo',
            ]
        );

        $ch9_1 = Chapter::firstOrCreate(
            ['course_id' => $course9->id, 'order' => 1],
            ['title' => 'Module 1 : Le cadre légal du numérique et la protection de la vie privée']
        );

        Lesson::firstOrCreate(
            ['chapter_id' => $ch9_1->id, 'order' => 1],
            [
                'title' => 'Synthèse juridique : Ce que dit la loi béninoise sur la diffamation en ligne',
                'content_type' => 'text',
                'content' => "Le Code du Numérique en République du Bénin (Loi n° 2017-20) sanctionne sévèrement la publication ou le partage de données personnelles sans autorisation, l'injure publique et les fausses nouvelles susceptibles de troubler l'ordre public.\n\nLa liberté d'expression ne protège pas le harcèlement ni la divulgation malveillante de photos intimes.",
                'content_simplified' => "La loi protège chaque citoyen contre les insultes, le mensonge et le harcèlement sur Facebook, TikTok et WhatsApp.",
            ]
        );

        Lesson::firstOrCreate(
            ['chapter_id' => $ch9_1->id, 'order' => 2],
            [
                'title' => 'Podcast Audio : Conseils d\'un juriste pour réagir face au cyberharcèlement',
                'content_type' => 'audio',
                'content' => "Écoute d'un entretien de 5 minutes avec Maître Christian Kpadonou sur la démarche à suivre auprès de l'Office Central de Répression de la Cybercriminalité (OCRC) en cas d'attaque numérique.",
                'audio_url' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3',
                'transcription' => 'Entretien audio sur les démarches de signalement auprès de l\'OCRC au Bénin.',
            ]
        );

        // Enroll test learner Farida Mama into Course 1 and Course 6
        $learner = User::where('email', 'apprenant@voici-emi.bj')->first();
        if ($learner) {
            CourseEnrollment::firstOrCreate(
                ['user_id' => $learner->id, 'course_id' => $course1->id],
                ['status' => 'in_progress', 'enrolled_at' => now()->subDays(5)]
            );
            CourseEnrollment::firstOrCreate(
                ['user_id' => $learner->id, 'course_id' => $course6->id],
                ['status' => 'in_progress', 'enrolled_at' => now()->subDays(2)]
            );
        }
    }
}
