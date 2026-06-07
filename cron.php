<?php
/**
 * DocumentaryHub - YouTube Auto-Fetch Cron Script
 * ================================================
 * Fetches latest videos from documentary YouTube channels
 * and saves them to the database.
 *
 * SETUP:
 *   1. Add your YouTube Data API v3 key to includes/config.php
 *   2. Run manually:  php /path/to/documentaryhub/cron.php
 *   3. Schedule via crontab:
 *      0 6 * * * php /var/www/html/documentaryhub/cron.php >> /var/log/dh_cron.log 2>&1
 *      (Runs every day at 6 AM)
 *
 * Get a free API key at: https://console.developers.google.com/
 */

// CLI or web check
define('RUNNING_CRON', true);
require_once __DIR__ . '/includes/config.php';

// ---- Configuration ----
$maxResultsPerChannel = 10; // Videos to fetch per channel per run
$defaultCategoryId    = 1;  // Fallback category (Nature & Wildlife)

// Documentary channels to monitor (Channel ID => Display Name)
$channels = [

    'UCknLrEdhRCp1aegoMqRaCZg' => 'DW Documentary',
    'UCVGbzmcFIBVDmGCKrOCPFYw' => 'Free Documentary',
    'UCwmZiChSryoWQCZMIQezgTg' => 'BBC Earth',
    'UCpVm7bg6pXKo1Pr6k5kxG9A' => 'National Geographic',
    'UCjFku5_8wCLBZdHCpRCNblA' => 'PBS',
    'UCNWmH1L9bNq52RPtjTYhKyA' => 'Absolute History',
    'UCODHrzPMGbNv67e84WDZhQQ' => 'fern',
    'UCHnyfMqiRRG1u-2MsSQLbXA' => 'Veritasium',
    'UC6n8I1UDTKP1IWjQMg6_TwA' => 'The B1M',
    'UCMOqf8ab-42UUQIdVoKwjlQ' => 'Practical Engineering',

    // Science & Technology
    'UC6107grRI4m0o2-emgoDnAA' => 'SmarterEveryDay',
    'UCsooa4yRKGN_zEE8iknghZA' => 'TED',
    'UCsXVk37bltHxD1rDPwtNM8Q' => 'Kurzgesagt',
    'UCY1kMZp36IQSyNx_9h4mpCg' => 'Mark Rober',
    'UC4a-Gbdw7vOaccHmFo40b9g' => 'Real Engineering',
    'UCqZQJ4600a9wIfMPbYc60Ow' => 'Mustard',
    'UC2bkHVIDjXS7sgrgjFtzOXQ' => 'Branch Education',
    'UCvQECJukTDE2i6aCoMnS-Vg' => 'ColdFusion',
    'UCfMJ2MchTSW2kWaT0kK94Yw' => 'Asianometry',

    // Space
    'UCciQ8wFcVoIIMi-lfu8-cjQ' => 'Astrum',
    'UCeWnylKamA4JQ_hjPrd74Fw' => 'SEA',
    'UC7_gcs09iThXybpVgjHZ_7g' => 'History of the Universe',
    'UCtFyitnJ6gUIhLt_cD7A4-g' => 'History of the Earth',
    'UCZFipeZtQM5CKUjx6grh54g' => 'PBS Space Time',

    // History
    'UC9MAhZQQd9egwWCxrwSIsJQ' => 'Timeline',
    'UCc-N24Y5OA0gqbjBwe1ttfA' => 'History Hit',
    'UCNIuvl7V8zACPpTmmNIqP2A' => 'Kings and Generals',
    'UCX7katl3DVmch4D7LSvqbVQ' => 'The Operations Room',
    'UC510QYlOlKNyhy_zdQxnGYw' => 'The Armchair Historian',
    'UC22BdTgxefuvUivrjesETjg' => 'Simple History',
    'UC6107grRI4m0o2-emgoDnAA' => 'Biographics',

    // Geopolitics & Current Affairs
    'UCWN3xxRkmTPmbKwht9FuE5A' => 'Johnny Harris',
    'UC7TjGGz6P1bVgJf4n3vFhNQ' => 'VisualPolitik',
    'UCrTTBSUr0zhPU56UQljag5A' => 'Caspian Report',
    'UCc-N24Y5OA0gqbjBwe1ttfA' => 'Warographics',
    'UCaO6VoaYJv4kS-TQO_M-N_g' => 'Economics Explained',

    // Society & Culture
    'UCvJJ_dzjViJCoLf5uKUTwoA' => 'VICE',
    'UCqnbDFdCpuN8CMEg0VuEBqA' => 'Real Stories',
    'UCmGSJVG3mCRXVOP4yZrU1Dw' => 'Journeyman Pictures',
    'UC4mLlRa_dezwvytudo9s1sw' => 'Business Insider',
    'UCzQUP1qoWDoEbmsQxvdjxgQ' => 'Bloomberg Originals',

    // Crime
    'UCNS3aLe8Riwl8Xh1e8Y3RWQ' => 'Coffeehouse Crime',
    'UCm1nC0Rj994QY7A8P7fN8HQ' => 'That Chapter',
    'UCn8zNIfYAQNdrFRrr8oibKw' => 'Explore With Us',

    // Environment
    'UC6AlY9I4xNfN3fXf6bKj3Qg' => 'Climate Town',
    'UCwRH985XgMYXQ6NxXDo8npw' => 'Our Changing Climate',

    // Art & Culture
    'UC6rTP3R8V5tj4Q4Xf6lF6xA' => 'Great Art Explained',
    'UCJ-UHmaA6I4W-wX5rD5jK1Q' => 'The Art Assignment',

    // Architecture & Engineering
    'UCcXwc0ArDa6a2ew5nFyFFDQ' => 'Megaprojects',
    'UCVH8lH7ZLDUeYpYj2PuLkBA' => 'MegaBuilds',
    'UC0woBco6Dgcxt0h8SwyyOmw' => 'Tomorrows Build',

];

// Category mapping: keywords in title → category_id
// (You can expand this list)
$categoryMap = [
   /* ========================== 1. NATURE & WILDLIFE ========================== */ 'nature'=>1,'wildlife'=>1,'animal'=>1,'animals'=>1,'forest'=>1, 'rainforest'=>1,'jungle'=>1,'bird'=>1,'birds'=>1,'lion'=>1, 'tiger'=>1,'elephant'=>1,'wolf'=>1,'bear'=>1,'leopard'=>1, 'cheetah'=>1,'rhino'=>1,'giraffe'=>1,'zebra'=>1,'monkey'=>1, 'ape'=>1,'gorilla'=>1,'orangutan'=>1,'panda'=>1,'koala'=>1, 'kangaroo'=>1,'penguin'=>1,'whale'=>1,'dolphin'=>1,'shark'=>1, 'octopus'=>1,'marine life'=>1,'coral reef'=>1,'ecosystem'=>1, 'species'=>1,'habitat'=>1,'migration'=>1,'predator'=>1, 'conservation'=>1,'biodiversity'=>1,'flora'=>1,'fauna'=>1, 'savanna'=>1,'amazon rainforest'=>1,'bbc earth'=>1, 'national geographic'=>1,'animal planet'=>1,'ocean'=>1, 'sea'=>1,'river'=>1,'lake'=>1, /* ========================== 2. SCIENCE & TECHNOLOGY ========================== */ 'science'=>2,'technology'=>2,'tech'=>2,'engineering'=>2, 'physics'=>2,'chemistry'=>2,'biology'=>2,'robotics'=>2, 'robot'=>2,'artificial intelligence'=>2,'ai'=>2, 'machine learning'=>2,'deep learning'=>2,'computer'=>2, 'software'=>2,'hardware'=>2,'programming'=>2,'coding'=>2, 'internet'=>2,'network'=>2,'cybersecurity'=>2,'hacking'=>2, 'data science'=>2,'algorithm'=>2,'quantum'=>2, 'quantum computing'=>2,'nanotechnology'=>2,'automation'=>2, 'semiconductor'=>2,'chip'=>2,'processor'=>2,'server'=>2, 'data center'=>2,'startup'=>2,'innovation'=>2, 'construction'=>2,'building'=>2,'bridge'=>2,'dam'=>2, 'tunnel'=>2,'skyscraper'=>2,'megaproject'=>2, 'infrastructure'=>2,'civil engineering'=>2, 'architecture'=>2,'airport'=>2,'railway'=>2,'metro'=>2, 'highway'=>2,'road'=>2,'factory'=>2,'industrial'=>2, 'manufacturing'=>2,'aviation'=>2,'aircraft'=>2, 'airplane'=>2,'boeing'=>2,'airbus'=>2,'fighter jet'=>2, 'drone'=>2,'helicopter'=>2,'ship'=>2,'ships'=>2, 'maritime'=>2,'submarine'=>2,'container ship'=>2, 'real engineering'=>2,'practical engineering'=>2, 'veritasium'=>2,'mustard'=>2,'coldfusion'=>2, /* ========================== 3. HISTORY ========================== */ 'history'=>3,'historical'=>3,'ancient'=>3,'archaeology'=>3, 'archaeological'=>3,'artifact'=>3,'civilization'=>3, 'empire'=>3,'dynasty'=>3,'kingdom'=>3,'king'=>3, 'queen'=>3,'rome'=>3,'roman'=>3,'greece'=>3,'greek'=>3, 'egypt'=>3,'pharaoh'=>3,'viking'=>3,'samurai'=>3, 'medieval'=>3,'middle ages'=>3,'renaissance'=>3, 'bronze age'=>3,'iron age'=>3,'mughal'=>3, 'british empire'=>3,'colonial'=>3,'lost civilization'=>3, 'forgotten history'=>3,'indus valley'=>3,'harappa'=>3, 'mohenjo daro'=>3,'mesopotamia'=>3,'mayan'=>3, 'aztec'=>3,'ancient egypt'=>3,'ancient rome'=>3, 'ancient greece'=>3,'pyramid'=>3,'ruins'=>3, 'timeline'=>3,'history hit'=>3,'oversimplified'=>3, /* ========================== 4. SOCIETY & CULTURE ========================== */ 'society'=>4,'culture'=>4,'people'=>4,'community'=>4, 'family'=>4,'lifestyle'=>4,'tradition'=>4,'custom'=>4, 'festival'=>4,'religion'=>4,'language'=>4,'tribe'=>4, 'tribal'=>4,'education'=>4,'poverty'=>4,'migration'=>4, 'urban'=>4,'rural'=>4,'anthropology'=>4,'sociology'=>4, 'human rights'=>4,'civil rights'=>4,'population'=>4, 'demography'=>4,'identity'=>4,'ethnicity'=>4, 'economy'=>4,'economics'=>4,'economic'=>4, 'business'=>4,'finance'=>4,'banking'=>4, 'capitalism'=>4,'inflation'=>4,'recession'=>4, 'startup'=>4,'entrepreneur'=>4,'company'=>4, 'corporation'=>4,'industry'=>4,'social media'=>4, 'internet culture'=>4,'human behavior'=>4, 'story'=>4,'stories'=>4,'explained'=>4,'mystery'=>4, 'mysteries'=>4,'fern'=>4,'real stories'=>4, 'dw documentary'=>4,'vice'=>4, /* ========================== 5. SPACE & UNIVERSE ========================== */ 'space'=>5,'universe'=>5,'cosmos'=>5,'nasa'=>5, 'galaxy'=>5,'astronomy'=>5,'astrophysics'=>5, 'planet'=>5,'star'=>5,'stars'=>5,'black hole'=>5, 'nebula'=>5,'milky way'=>5,'solar system'=>5, 'exoplanet'=>5,'moon'=>5,'mars'=>5,'jupiter'=>5, 'saturn'=>5,'rocket'=>5,'spacex'=>5,'satellite'=>5, 'iss'=>5,'international space station'=>5, 'big bang'=>5,'dark matter'=>5,'dark energy'=>5, 'alien'=>5,'extraterrestrial'=>5,'telescope'=>5, 'james webb'=>5,'astrum'=>5,'pbs space time'=>5, /* ========================== 6. ENVIRONMENT & CLIMATE ========================== */ 'environment'=>6,'climate'=>6,'climate change'=>6, 'global warming'=>6,'pollution'=>6,'carbon'=>6, 'emissions'=>6,'greenhouse gas'=>6,'renewable energy'=>6, 'solar energy'=>6,'wind energy'=>6,'ecology'=>6, 'sustainability'=>6,'recycling'=>6,'wildfire'=>6, 'flood'=>6,'drought'=>6,'earthquake'=>6, 'landslide'=>6,'natural disaster'=>6,'weather'=>6, 'environmental'=>6,'green energy'=>6,'sea level rise'=>6, 'our planet'=>6,'climate town'=>6, /* ========================== 7. POLITICS & WAR ========================== */ 'politics'=>7,'political'=>7,'government'=>7, 'election'=>7,'democracy'=>7,'dictatorship'=>7, 'president'=>7,'prime minister'=>7,'parliament'=>7, 'war'=>7,'battle'=>7,'military'=>7,'army'=>7, 'navy'=>7,'air force'=>7,'ww1'=>7,'ww2'=>7, 'wwii'=>7,'cold war'=>7,'nuclear'=>7, 'conflict'=>7,'revolution'=>7,'weapon'=>7, 'geopolitics'=>7,'geopolitical'=>7, 'foreign policy'=>7,'international relations'=>7, 'diplomacy'=>7,'trade war'=>7,'nato'=>7, 'united nations'=>7,'sanctions'=>7, 'strategic affairs'=>7,'world order'=>7, 'china'=>7,'russia'=>7,'usa'=>7,'ukraine'=>7, 'middle east'=>7,'taiwan'=>7, 'news'=>7,'breaking news'=>7,'current affairs'=>7, 'world news'=>7,'journalism'=>7, 'johnny harris'=>7,'visualpolitik'=>7, 'caspian report'=>7,'warographics'=>7, /* ========================== 8. CRIME & JUSTICE ========================== */ 'crime'=>8,'criminal'=>8,'murder'=>8, 'serial killer'=>8,'detective'=>8,'investigation'=>8, 'forensic'=>8,'forensics'=>8,'police'=>8, 'court'=>8,'judge'=>8,'lawyer'=>8,'justice'=>8, 'prison'=>8,'mafia'=>8,'cartel'=>8,'gang'=>8, 'fraud'=>8,'scam'=>8,'kidnapping'=>8, 'cold case'=>8,'true crime'=>8, 'cybercrime'=>8,'interrogation'=>8, 'coffeehouse crime'=>8, /* ========================== 9. HEALTH & MEDICINE ========================== */ 'health'=>9,'medicine'=>9,'medical'=>9, 'doctor'=>9,'hospital'=>9,'disease'=>9, 'virus'=>9,'pandemic'=>9,'vaccine'=>9, 'brain'=>9,'body'=>9,'human body'=>9, 'surgery'=>9,'mental health'=>9, 'psychology'=>9,'neuroscience'=>9, 'nutrition'=>9,'fitness'=>9,'cancer'=>9, 'heart'=>9,'dna'=>9,'genetics'=>9, 'therapy'=>9,'pharmaceutical'=>9, 'medlife crisis'=>9, /* ========================== 10. ART & MUSIC ========================== */ 'art'=>10,'music'=>10,'film'=>10,'cinema'=>10, 'artist'=>10,'painting'=>10,'sculpture'=>10, 'photography'=>10,'design'=>10,'musician'=>10, 'composer'=>10,'band'=>10,'rock'=>10, 'jazz'=>10,'classical music'=>10,'dance'=>10, 'theater'=>10,'opera'=>10,'creative'=>10, 'documentary film'=>10,'movie'=>10, 'great art explained'=>10,'arte'=>10,
];

// -------------------------------------------------------
// FUNCTIONS
// -------------------------------------------------------

function log_msg(string $msg): void {
    $ts = date('[Y-m-d H:i:s]');
    echo "$ts $msg\n";
}

function fetchYouTubeVideos(string $channelId, int $maxResults = 10): array {
    $apiKey = YOUTUBE_API_KEY;

    if ($apiKey === 'YOUR_YOUTUBE_API_KEY_HERE') {
        log_msg("⚠  YouTube API key not configured. Edit includes/config.php");
        return [];
    }

    $url = "https://www.googleapis.com/youtube/v3/search?" . http_build_query([
        'key'         => $apiKey,
        'channelId'   => $channelId,
        'part'        => 'snippet',
        'order'       => 'date',
        'type'        => 'video',
        'maxResults'  => $maxResults,
        'videoDuration' => 'long', // Only videos > 20 minutes
    ]);

    $context = stream_context_create(['http' => ['timeout' => 15]]);
    $resp    = @file_get_contents($url, false, $context);

    if ($resp === false) {
        log_msg("✗  Failed to fetch from YouTube API for channel $channelId");
        return [];
    }

    $data = json_decode($resp, true);

    if (isset($data['error'])) {
        log_msg("✗  YouTube API error: " . $data['error']['message']);
        return [];
    }

    return $data['items'] ?? [];
}

function detectCategory(string $title, array $categoryMap, int $default): int {
    $lower = strtolower($title);
    foreach ($categoryMap as $keyword => $catId) {
        if (strpos($lower, $keyword) !== false) {
            return $catId;
        }
    }
    return $default;
}

function parseDuration(string $iso8601): string {
    preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $iso8601, $m);
    $h = (int)($m[1] ?? 0);
    $i = (int)($m[2] ?? 0);
    // $s = (int)($m[3] ?? 0); // seconds not shown
    if ($h > 0) return "{$h}h {$i}m";
    if ($i > 0) return "{$i} min";
    return 'N/A';
}

// -------------------------------------------------------
// MAIN LOOP
// -------------------------------------------------------

log_msg("=== DocumentaryHub Cron Job Started ===");
$db      = getDB();
$added   = 0;
$skipped = 0;
$errors  = 0;

foreach ($channels as $channelId => $channelName) {
    log_msg("→  Fetching from: $channelName ($channelId)");

    $videos = fetchYouTubeVideos($channelId, $maxResultsPerChannel);

    if (empty($videos)) {
        log_msg("   No videos returned.");
        continue;
    }

    // Optionally fetch video details (duration, etc.) via videos.list
    $videoIds = array_map(fn($v) => $v['id']['videoId'] ?? '', $videos);
    $videoIds = array_filter($videoIds);
    $detailsMap = [];

    if (!empty($videoIds) && YOUTUBE_API_KEY !== 'YOUR_YOUTUBE_API_KEY_HERE') {
        $detailUrl = "https://www.googleapis.com/youtube/v3/videos?" . http_build_query([
            'key'  => YOUTUBE_API_KEY,
            'id'   => implode(',', $videoIds),
            'part' => 'contentDetails,statistics',
        ]);
        $context = stream_context_create(['http' => ['timeout' => 15]]);
        $resp    = @file_get_contents($detailUrl, false, $context);
        if ($resp) {
            $details = json_decode($resp, true);
            foreach ($details['items'] ?? [] as $item) {
                $detailsMap[$item['id']] = $item;
            }
        }
    }

    foreach ($videos as $video) {
        $videoId = $video['id']['videoId'] ?? '';
        if (empty($videoId)) continue;

        $snippet = $video['snippet'];
        $title   = trim($snippet['title'] ?? '');
        $desc    = trim($snippet['description'] ?? '');
        $pubDate = $snippet['publishedAt'] ?? null;
        $thumb   = $snippet['thumbnails']['maxres']['url']
                ?? $snippet['thumbnails']['high']['url']
                ?? "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";

        if (empty($title)) { $errors++; continue; }

        // Check duplicate
        $dup = $db->prepare("SELECT id FROM documentaries WHERE youtube_video_id = ?");
        $dup->execute([$videoId]);
        if ($dup->fetch()) {
            log_msg("   SKIP (duplicate): $title");
            $skipped++;
            continue;
        }

        // Duration from details
        $duration = 'N/A';
        $year     = $pubDate ? (int)date('Y', strtotime($pubDate)) : null;
        if (isset($detailsMap[$videoId]['contentDetails']['duration'])) {
            $duration = parseDuration($detailsMap[$videoId]['contentDetails']['duration']);
        }

        // Category detection
        $catId = detectCategory($title, $categoryMap, $defaultCategoryId);

        // Generate slug
        $slug     = generateSlug($title);
        $slugBase = $slug;
        $slugN    = 1;
        while (true) {
            $s = $db->prepare("SELECT id FROM documentaries WHERE slug = ?");
            $s->execute([$slug]);
            if (!$s->fetch()) break;
            $slug = $slugBase . '-' . (++$slugN);
        }

        // Insert
        try {
            $stmt = $db->prepare("
                INSERT INTO documentaries
                    (title, slug, description, category_id, thumbnail, youtube_video_id,
                     source, duration, year, is_active, created_at)
                VALUES (?,?,?,?,?,?,?,?,?,1,?)
            ");
            $stmt->execute([
                $title, $slug, substr($desc, 0, 1000), $catId, $thumb, $videoId,
                $channelName, $duration, $year,
                $pubDate ? date('Y-m-d H:i:s', strtotime($pubDate)) : date('Y-m-d H:i:s')
            ]);
            log_msg("   ✓ Added: $title ($videoId)");
            $added++;
        } catch (\PDOException $e) {
            log_msg("   ✗ DB Error: " . $e->getMessage());
            $errors++;
        }
    }

    // Brief pause to respect API rate limits
    sleep(1);
}

log_msg("=== Finished: +$added added | $skipped skipped | $errors errors ===");
