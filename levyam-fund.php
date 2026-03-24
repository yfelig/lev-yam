<?php
// =====================================================
//  לב ים — עמוד גיוס המונים
//  Lev Yam Crowdfunding Page
//  ─────────────────────────────────────────────────
//  Upload this file + assets/ folder to your WordPress
//  root (public_html/) via GoDaddy cPanel.
//  Fill in your Grow Payments credentials below.
// =====================================================

// ─── CONFIG — מלא את הפרטים שלך ────────────────────
define('GROW_PAGE_CODE', 'YOUR_PAGE_CODE');   // קוד עמוד מ-Grow
define('GROW_USER_ID',   'YOUR_USER_ID');     // מזהה משתמש מ-Grow
define('GROW_API_KEY',   'YOUR_API_KEY');     // מפתח API מ-Grow
define('GROW_API_URL',   'https://api.grow-il.co.il/api/createPaymentProcess');
define('GOAL_AMOUNT',    250000);
define('DATA_FILE',      __DIR__ . '/levyam-data.json');

// ─── INIT DATA FILE ──────────────────────────────────
if (!file_exists(DATA_FILE)) {
    file_put_contents(DATA_FILE, json_encode(['total' => 0, 'backers' => 0]));
}

// ─── AJAX: CREATE PAYMENT SESSION ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_payment') {
    header('Content-Type: application/json; charset=utf-8');

    $amount = (int)($_POST['amount'] ?? 0);
    $name   = trim(strip_tags($_POST['name']  ?? ''));
    $email  = trim(strip_tags($_POST['email'] ?? ''));
    $phone  = trim(strip_tags($_POST['phone'] ?? ''));

    if ($amount <= 0 || $name === '' || $email === '') {
        http_response_code(400);
        echo json_encode(['error' => 'פרטים חסרים']);
        exit;
    }

    $baseUrl    = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
    $successUrl = $baseUrl . '?status=success&amount=' . $amount;
    $cancelUrl  = $baseUrl . '?status=cancel';

    $ch = curl_init(GROW_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'pageCode'      => GROW_PAGE_CODE,
            'userId'        => GROW_USER_ID,
            'apiKey'        => GROW_API_KEY,
            'sum'           => $amount,
            'successUrl'    => $successUrl,
            'cancelUrl'     => $cancelUrl,
            'description'   => 'תמיכה בלב ים – ' . $name,
            'customerEmail' => $email,
            'customerPhone' => $phone,
            'customerName'  => $name,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || $httpCode !== 200) {
        http_response_code(502);
        echo json_encode(['error' => 'שגיאה בחיבור למערכת התשלום. נסה שוב.']);
        exit;
    }

    $result = json_decode($response, true);
    $url    = $result['url'] ?? $result['data']['url'] ?? null;

    if ($url) {
        echo json_encode(['url' => $url]);
    } else {
        http_response_code(502);
        echo json_encode(['error' => 'לא התקבל קישור תשלום. פנה לתמיכה.']);
    }
    exit;
}

// ─── READ PROGRESS ───────────────────────────────────
$data    = json_decode(file_get_contents(DATA_FILE), true) ?? ['total' => 0, 'backers' => 0];
$total   = max(0, (int)($data['total']   ?? 0));
$backers = max(0, (int)($data['backers'] ?? 0));
$percent = min(100, round(($total / GOAL_AMOUNT) * 100, 1));

$showSuccess = ($_GET['status'] ?? '') === 'success';
$showCancel  = ($_GET['status'] ?? '') === 'cancel';

// ─── TIERS ───────────────────────────────────────────
$tiers = [
    [
        'id'      => 1,
        'name'    => 'חבר הים',
        'price'   => 180,
        'icon'    => '🐚',
        'tagline' => 'צעד ראשון, חיבור אמיתי',
        'perks'   => ['שמך בקיר המייסדים', 'עדכונים שוטפים ממעמקי הבנייה', 'תודה אישית מהצוות'],
        'popular' => false,
    ],
    [
        'id'      => 2,
        'name'    => 'עוגן',
        'price'   => 550,
        'icon'    => '⚓',
        'tagline' => 'שורש עמוק בקרקע',
        'perks'   => ['כל מה שבחבר הים', 'יום עבודה על החוף לאחר הפתיחה', 'גישה לניוזלטר המייסדים'],
        'popular' => false,
    ],
    [
        'id'      => 3,
        'name'    => 'קברניט',
        'price'   => 1800,
        'icon'    => '🧭',
        'tagline' => 'הכי פופולרי',
        'perks'   => ['כל מה שבעוגן', '3 ימי עבודה על החוף', 'הזמנה לאירוע חגיגת הפתיחה', 'הנחת מייסד 20% לנצח'],
        'popular' => true,
    ],
    [
        'id'      => 4,
        'name'    => 'בעל הסירה',
        'price'   => 5000,
        'icon'    => '⛵',
        'tagline' => 'שותפות עמוקה',
        'perks'   => ['כל מה שבקברניט', 'גישה חופשית לחודשיים שלמים', 'לוח הכרה נצחי בלב ים', 'ישיבה אחת עם המייסדים'],
        'popular' => false,
    ],
];

function formatNIS(int $n): string {
    return '₪' . number_format($n, 0, '.', ',');
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>לב ים — גיוס המונים</title>
<meta name="description" content="מבנה דייגים עתיק על חוף כמעט מבודד. עזרו לנו להקים מרחב עבודה יחיד במינו על שפת הים.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
<!-- Design matches Lev Yam logo: blue #3B9BC8 + orange #F5952A -->
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --blue:      #3B9BC8;
  --blue-dk:   #1A5276;
  --blue-nav:  #1D3A52;
  --blue-lt:   #EBF6FF;
  --orange:    #F5952A;
  --orange-lt: #FFF4E3;
  --white:     #FFFFFF;
  --text:      #1A3C5E;
  --muted:     #5D7A8A;
  --border:    #D6EAF8;
  /* legacy aliases kept for compatibility */
  --sea:    #3B9BC8;
  --sea-lt: #6db8d8;
  --dark:   #1D3A52;
  --cream:  #EBF6FF;
  --cream2: #dceefa;
  --gold:   #F5952A;
  --sand:   #b0d4ea;
  --radius: 18px;
  --shadow: 0 8px 32px rgba(26,82,118,.12);
}

html { scroll-behavior: smooth; }

body {
  font-family: 'Rubik', sans-serif;
  background: var(--cream);
  color: var(--dark);
  line-height: 1.6;
  overflow-x: hidden;
}

/* ── HERO ─────────────────────────────────────────── */
.hero {
  position: relative;
  height: 100vh;
  min-height: 600px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  overflow: hidden;
}

.hero-video {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: 0;
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    160deg,
    rgba(29,58,82,.72) 0%,
    rgba(59,155,200,.45) 50%,
    rgba(29,58,82,.80) 100%
  );
  z-index: 1;
}

.hero-content {
  position: relative;
  z-index: 2;
  padding: 2rem;
  max-width: 780px;
}

.hero-logo {
  font-size: clamp(3.5rem, 10vw, 7rem);
  font-weight: 900;
  color: var(--white);
  letter-spacing: -2px;
  line-height: 1;
  text-shadow: 0 4px 24px rgba(0,0,0,.4);
}

.hero-logo span { color: var(--gold); }

.hero-tagline {
  font-size: clamp(1rem, 3vw, 1.4rem);
  font-weight: 300;
  color: rgba(253,250,246,.88);
  margin-top: 1rem;
  letter-spacing: .5px;
}

.hero-cta {
  display: inline-block;
  margin-top: 2.5rem;
  padding: .9rem 2.8rem;
  background: var(--gold);
  color: var(--dark);
  font-size: 1.1rem;
  font-weight: 700;
  border-radius: 50px;
  text-decoration: none;
  transition: transform .2s, box-shadow .2s;
  box-shadow: 0 6px 24px rgba(212,168,67,.4);
}
.hero-cta:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(212,168,67,.5); }

.scroll-hint {
  position: absolute;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2;
  color: rgba(255,255,255,.6);
  font-size: .8rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .5rem;
  animation: bounce 2s infinite;
}
.scroll-hint svg { width: 24px; height: 24px; }

@keyframes bounce {
  0%,100% { transform: translateX(-50%) translateY(0); }
  50%      { transform: translateX(-50%) translateY(8px); }
}

/* ── SECTIONS ─────────────────────────────────────── */
section { padding: 5rem 1.5rem; }
.container { max-width: 1100px; margin: 0 auto; }
.section-label {
  font-size: .8rem;
  font-weight: 600;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--sea);
  margin-bottom: .75rem;
}
.section-title {
  font-size: clamp(1.8rem, 4vw, 2.8rem);
  font-weight: 800;
  color: var(--dark);
  line-height: 1.2;
}
.section-title span { color: var(--sea); }

/* ── STORY ────────────────────────────────────────── */
.story { background: var(--white); }
.story-inner {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;
}
.story-image {
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: var(--shadow);
  aspect-ratio: 4/3;
}
.story-image img { width: 100%; height: 100%; object-fit: cover; }
.story-text p {
  font-size: 1.1rem;
  font-weight: 300;
  color: #4a3d35;
  margin-top: 1.5rem;
  line-height: 1.9;
}
.story-text p strong { font-weight: 600; color: var(--dark); }

/* ── PROGRESS ─────────────────────────────────────── */
.progress-section { background: var(--dark); color: var(--white); text-align: center; }
.progress-section .section-title { color: var(--white); }
.progress-section .section-title span { color: var(--gold); }

.progress-stats {
  display: flex;
  justify-content: center;
  gap: 4rem;
  margin: 2.5rem 0;
  flex-wrap: wrap;
}
.stat-item { }
.stat-number {
  font-size: clamp(2rem, 5vw, 3.5rem);
  font-weight: 900;
  color: var(--gold);
  display: block;
  line-height: 1;
}
.stat-label {
  font-size: .9rem;
  font-weight: 300;
  color: rgba(253,250,246,.6);
  margin-top: .3rem;
}

.progress-bar-wrap {
  background: rgba(255,255,255,.1);
  border-radius: 50px;
  height: 18px;
  max-width: 700px;
  margin: 0 auto 1rem;
  overflow: hidden;
}
.progress-bar-fill {
  height: 100%;
  border-radius: 50px;
  background: linear-gradient(90deg, var(--sea), var(--gold));
  width: 0%;
  transition: width 1.8s cubic-bezier(.25,.8,.25,1);
}
.progress-percent {
  font-size: .9rem;
  color: rgba(253,250,246,.55);
}

/* ── TIERS ────────────────────────────────────────── */
.tiers { background: var(--cream); }
.tiers-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
  margin-top: 3rem;
}
.tier-card {
  background: var(--white);
  border-radius: var(--radius);
  padding: 2rem 1.5rem;
  box-shadow: 0 4px 16px rgba(45,37,32,.08);
  transition: transform .25s, box-shadow .25s;
  position: relative;
  display: flex;
  flex-direction: column;
  border: 2px solid transparent;
  cursor: pointer;
}
.tier-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 16px 40px rgba(45,37,32,.15);
}
.tier-card.popular {
  border-color: var(--gold);
  box-shadow: 0 8px 32px rgba(212,168,67,.25);
}
.tier-popular-badge {
  position: absolute;
  top: -14px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--gold);
  color: var(--dark);
  font-size: .75rem;
  font-weight: 700;
  padding: .3rem 1rem;
  border-radius: 50px;
  white-space: nowrap;
}
.tier-icon { font-size: 2.2rem; margin-bottom: .75rem; }
.tier-name {
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--dark);
  margin-bottom: .25rem;
}
.tier-price {
  font-size: 2rem;
  font-weight: 900;
  color: var(--sea);
  line-height: 1;
  margin-bottom: 1.25rem;
}
.tier-price sup { font-size: 1rem; vertical-align: super; }
.tier-perks {
  list-style: none;
  flex: 1;
  margin-bottom: 1.5rem;
}
.tier-perks li {
  font-size: .9rem;
  color: #5a4d45;
  padding: .35rem 0;
  border-bottom: 1px solid rgba(200,168,130,.2);
  display: flex;
  align-items: flex-start;
  gap: .5rem;
}
.tier-perks li::before {
  content: '✓';
  color: var(--sea);
  font-weight: 700;
  flex-shrink: 0;
  margin-top: .05rem;
}
.tier-btn {
  width: 100%;
  padding: .8rem;
  border: none;
  border-radius: 50px;
  background: var(--sea);
  color: white;
  font-family: 'Rubik', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: background .2s, transform .15s;
}
.tier-btn:hover { background: var(--wood); transform: scale(1.02); }
.tier-card.popular .tier-btn { background: var(--gold); color: var(--dark); }
.tier-card.popular .tier-btn:hover { background: #c49635; }

/* ── GALLERY ──────────────────────────────────────── */
.gallery { background: var(--dark); padding: 4rem 0; overflow: hidden; }
.gallery-title-wrap { padding: 0 1.5rem; margin-bottom: 2rem; }
.gallery-title-wrap .section-title { color: var(--white); }
.gallery-scroll {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  padding: 0 1.5rem 1rem;
  scroll-snap-type: x mandatory;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.gallery-scroll::-webkit-scrollbar { display: none; }
.gallery-item {
  flex-shrink: 0;
  width: 320px;
  height: 240px;
  border-radius: var(--radius);
  overflow: hidden;
  scroll-snap-align: start;
  box-shadow: 0 8px 24px rgba(0,0,0,.4);
}
.gallery-item img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
.gallery-item:hover img { transform: scale(1.05); }

/* ── ABOUT ────────────────────────────────────────── */
.about { background: var(--cream2); }
.about-inner {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;
}
.about-image {
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: var(--shadow);
  aspect-ratio: 4/3;
}
.about-image img { width: 100%; height: 100%; object-fit: cover; }
.about-text p {
  font-size: 1.05rem;
  color: #4a3d35;
  margin-top: 1.25rem;
  line-height: 1.9;
  font-weight: 300;
}

/* ── FOOTER ───────────────────────────────────────── */
footer {
  background: var(--dark);
  color: rgba(253,250,246,.55);
  text-align: center;
  padding: 3rem 1.5rem;
  font-size: .9rem;
}
footer strong { color: var(--gold); font-size: 1.4rem; display: block; margin-bottom: .75rem; }
footer a { color: var(--sea-lt); text-decoration: none; }
footer a:hover { text-decoration: underline; }

/* ── MODAL ────────────────────────────────────────── */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(45,37,32,.75);
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  opacity: 0;
  pointer-events: none;
  transition: opacity .25s;
}
.modal-backdrop.open { opacity: 1; pointer-events: all; }
.modal {
  background: var(--white);
  border-radius: var(--radius);
  padding: 2.5rem;
  width: 100%;
  max-width: 480px;
  max-height: 90vh;
  overflow-y: auto;
  transform: translateY(20px);
  transition: transform .25s;
  position: relative;
}
.modal-backdrop.open .modal { transform: translateY(0); }
.modal-close {
  position: absolute;
  top: 1rem;
  left: 1rem;
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--dark);
  line-height: 1;
}
.modal-tier-name { font-size: 1.5rem; font-weight: 800; margin-bottom: .25rem; }
.modal-tier-price { font-size: 2.2rem; font-weight: 900; color: var(--sea); margin-bottom: 1.5rem; }
.modal label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: .3rem; color: #4a3d35; }
.modal input {
  width: 100%;
  padding: .75rem 1rem;
  border: 1.5px solid #d8cfc5;
  border-radius: 10px;
  font-family: 'Rubik', sans-serif;
  font-size: 1rem;
  margin-bottom: 1rem;
  color: var(--dark);
  background: var(--cream);
  outline: none;
  direction: rtl;
  transition: border-color .2s;
}
.modal input:focus { border-color: var(--sea); }
.modal-submit {
  width: 100%;
  padding: 1rem;
  background: var(--sea);
  color: white;
  border: none;
  border-radius: 50px;
  font-family: 'Rubik', sans-serif;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  transition: background .2s;
  margin-top: .5rem;
}
.modal-submit:hover { background: var(--wood); }
.modal-submit:disabled { background: #aaa; cursor: not-allowed; }
.modal-error { color: #c0392b; font-size: .9rem; margin-top: .75rem; text-align: center; }

/* ── SUCCESS / CANCEL TOAST ───────────────────────── */
.toast {
  position: fixed;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%) translateY(100px);
  background: var(--dark);
  color: var(--white);
  padding: 1rem 2rem;
  border-radius: 50px;
  font-size: 1rem;
  font-weight: 500;
  box-shadow: var(--shadow);
  z-index: 200;
  transition: transform .4s cubic-bezier(.34,1.56,.64,1);
  white-space: nowrap;
  text-align: center;
}
.toast.show { transform: translateX(-50%) translateY(0); }
.toast.success { border-right: 5px solid var(--gold); }
.toast.cancel   { border-right: 5px solid #c0392b; }

/* ── RESPONSIVE ───────────────────────────────────── */
@media (max-width: 900px) {
  .tiers-grid { grid-template-columns: 1fr 1fr; }
  .story-inner, .about-inner { grid-template-columns: 1fr; gap: 2.5rem; }
  .story-image { order: -1; }
  .progress-stats { gap: 2.5rem; }
}
@media (max-width: 580px) {
  .tiers-grid { grid-template-columns: 1fr; }
  .gallery-item { width: 280px; height: 200px; }
  .modal { padding: 1.75rem 1.25rem; }
}
</style>
</head>
<body>

<?php if ($showSuccess): ?>
<div id="toast" class="toast success show">
  🎉 תודה! התרומה שלך התקבלה בהצלחה. ברוכים הבאים למשפחת לב ים!
</div>
<?php elseif ($showCancel): ?>
<div id="toast" class="toast cancel show">
  התשלום בוטל. אתם מוזמנים לנסות שוב בכל עת.
</div>
<?php endif; ?>

<!-- ── NAV ─────────────────────────────────────────── -->
<nav style="position:fixed;top:0;left:0;right:0;z-index:50;background:rgba(29,58,82,.92);backdrop-filter:blur(10px);padding:.9rem 2rem;display:flex;align-items:center;justify-content:space-between;">
  <img src="assets/logo.jpeg" alt="לב ים" style="height:42px;filter:brightness(0) invert(1);">
  <a href="#tiers" style="background:var(--orange);color:white;font-family:'Rubik',sans-serif;font-weight:700;font-size:.9rem;border:none;padding:.55rem 1.4rem;border-radius:50px;cursor:pointer;text-decoration:none;">הצטרפו לגיוס</a>
</nav>

<!-- ── HERO ─────────────────────────────────────────── -->
<section class="hero" id="top">
  <video class="hero-video" autoplay muted loop playsinline poster="assets/hero-workspace.jpeg">
    <source src="assets/hero-video.mp4" type="video/mp4">
  </video>
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <img src="assets/logo.jpeg" alt="לב ים"
         style="width:140px;height:auto;filter:brightness(0) invert(1);margin-bottom:1.5rem;display:block;margin-left:auto;margin-right:auto;">
    <h1 class="hero-logo" style="font-size:clamp(2.2rem,6vw,4rem);font-weight:900;color:white;line-height:1.15;text-shadow:0 3px 16px rgba(0,0,0,.35);">
      מרחב שפועם<br><span style="color:var(--orange)">על שפת הים</span>
    </h1>
    <p class="hero-tagline">
      מבנה דייגים עתיק, אבן כורכר, וים שמחכה.<br>
      עזרו לנו לבנות את לב ים — ביחד.
    </p>
    <div style="margin-top:2.5rem;display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="#tiers" class="hero-cta">הצטרפו לגיוס</a>
      <a href="#story" style="display:inline-block;padding:.95rem 2.2rem;background:transparent;color:white;font-size:1.05rem;font-weight:600;border-radius:50px;text-decoration:none;border:2px solid rgba(255,255,255,.55);">הסיפור שלנו</a>
    </div>
  </div>
  <div class="scroll-hint">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="6 9 12 15 18 9"/>
    </svg>
  </div>
</section>

<!-- ── STORY ─────────────────────────────────────────── -->
<section class="story" id="story">
  <div class="container">
    <div class="story-inner">
      <div class="story-image">
        <img src="assets/hero-workspace.jpeg" alt="עבודה על שפת הים בלב ים" loading="lazy">
      </div>
      <div class="story-text">
        <p class="section-label">הסיפור שלנו</p>
        <h2 class="section-title">מקום שנולד<br><span>מאהבה לים</span></h2>
        <p>
          בלב מישור החוף, עטוף אבן כורכר וצדפים, שוכן מבנה דייגים עתיק שהמתין להיות משהו שונה.
          <strong>לב ים</strong> הוא המקום הזה — מרחב עבודה, יצירה ומפגש, שצמח מתוך אמונה שהיצירה הטובה ביותר נולדת כשהגוף קרוב לים והנשמה שקטה.
        </p>
        <p>
          אנחנו שואפים לחיבור מדויק ומחייב עם א.נשים מוכשרים ומלאי תעוזה — שנכונים להגשים חלומות ולהפוך לחלק בלתי נפרד מהחוויה ומהצמיחה של לב ים.
          <strong>הגיוס הזה הוא ההתחלה.</strong>
        </p>
      </div>
    </div>
  </div>
</section>

<!-- ── PROGRESS ──────────────────────────────────────── -->
<section class="progress-section" id="progress">
  <div class="container">
    <p class="section-label" style="color:var(--sea-lt)">מצב הגיוס</p>
    <h2 class="section-title">כך אנחנו <span>בונים</span> את לב ים</h2>

    <div class="progress-stats">
      <div class="stat-item">
        <span class="stat-number" id="stat-raised"><?= formatNIS($total) ?></span>
        <span class="stat-label">גויסו עד כה</span>
      </div>
      <div class="stat-item">
        <span class="stat-number"><?= formatNIS(GOAL_AMOUNT) ?></span>
        <span class="stat-label">יעד הגיוס</span>
      </div>
      <div class="stat-item">
        <span class="stat-number" id="stat-backers"><?= $backers ?></span>
        <span class="stat-label">תומכים</span>
      </div>
    </div>

    <div class="progress-bar-wrap">
      <div class="progress-bar-fill" id="progress-fill" data-percent="<?= $percent ?>"></div>
    </div>
    <p class="progress-percent"><?= $percent ?>% מהיעד</p>
  </div>
</section>

<!-- ── TIERS ─────────────────────────────────────────── -->
<section class="tiers" id="tiers">
  <div class="container">
    <p class="section-label">חבילות תמיכה</p>
    <h2 class="section-title">בחרו את <span>חלקכם</span> בסיפור</h2>
    <div class="tiers-grid">
      <?php foreach ($tiers as $tier): ?>
      <div class="tier-card <?= $tier['popular'] ? 'popular' : '' ?>"
           onclick="openModal(<?= $tier['id'] ?>)">
        <?php if ($tier['popular']): ?>
          <div class="tier-popular-badge">⭐ <?= $tier['tagline'] ?></div>
        <?php endif; ?>
        <div class="tier-icon"><?= $tier['icon'] ?></div>
        <div class="tier-name"><?= $tier['name'] ?></div>
        <div class="tier-price"><sup>₪</sup><?= number_format($tier['price'], 0, '.', ',') ?></div>
        <ul class="tier-perks">
          <?php foreach ($tier['perks'] as $perk): ?>
            <li><?= htmlspecialchars($perk) ?></li>
          <?php endforeach; ?>
        </ul>
        <button class="tier-btn">הצטרף</button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── GALLERY ────────────────────────────────────────── -->
<section class="gallery" id="gallery">
  <div class="gallery-title-wrap">
    <p class="section-label" style="color:var(--sea-lt)">מהמקום</p>
    <h2 class="section-title" style="color:var(--white)">חצי <span style="color:var(--gold)">תמונה</span>, חצי חלום</h2>
  </div>
  <div class="gallery-scroll" id="galleryScroll">
    <div class="gallery-item"><img src="assets/img-table-sunset.jpeg"   alt="שולחן עץ בשקיעה"         loading="lazy"></div>
    <div class="gallery-item"><img src="assets/hero-workspace.jpeg"     alt="עבודה על שפת הים"         loading="lazy"></div>
    <div class="gallery-item"><img src="assets/img-woman-reading.jpeg"  alt="רגע של שקט"               loading="lazy"></div>
    <div class="gallery-item"><img src="assets/img-bar-counter.jpeg"    alt="בר עץ ישן"                loading="lazy"></div>
    <div class="gallery-item"><img src="assets/img-founders.jpeg"       alt="אנשי לב ים"               loading="lazy"></div>
    <div class="gallery-item"><img src="assets/img-extra1.jpeg"         alt="לב ים"                    loading="lazy"></div>
    <div class="gallery-item"><img src="assets/img-extra2.jpeg"         alt="חוף לב ים"                loading="lazy"></div>
  </div>
</section>

<!-- ── ABOUT ─────────────────────────────────────────── -->
<section class="about" id="about">
  <div class="container">
    <div class="about-inner">
      <div class="about-text">
        <p class="section-label">מי אנחנו</p>
        <h2 class="section-title">אנשים שאוהבים <span>מה שעושים</span></h2>
        <p>
          לב ים נוסד על ידי אנשים שמאמינים שהסביבה בה אנחנו עובדים ויוצרים — מעצבת את מה שאנחנו מייצרים.
          שכנים לים, לדייגים, לסלעים ולרוח, אנחנו בונים מרחב שמעניק תחושת תום, קרבה וביטחון באדם ובטבע.
        </p>
        <p>
          הגיוס הזה הוא ההזדמנות שלכם להיות חלק מהפרק הראשון.
          כי מה שנבנה כאן — נבנה יחד.
        </p>
      </div>
      <div class="about-image">
        <img src="assets/img-founders.jpeg" alt="מייסדי לב ים" loading="lazy">
      </div>
    </div>
  </div>
</section>

<!-- ── FOOTER ─────────────────────────────────────────── -->
<footer>
  <strong>לב ים</strong>
  <p>שאלות? כתבו לנו: <a href="mailto:info@levyam.co.il">info@levyam.co.il</a></p>
  <p style="margin-top:.75rem;font-size:.8rem;opacity:.5;">© <?= date('Y') ?> לב ים. כל הזכויות שמורות.</p>
</footer>

<!-- ── PAYMENT MODAL ──────────────────────────────────── -->
<div class="modal-backdrop" id="modalBackdrop">
  <div class="modal" role="dialog" aria-modal="true">
    <button class="modal-close" onclick="closeModal()" aria-label="סגור">✕</button>
    <div class="modal-tier-name" id="modalTierName"></div>
    <div class="modal-tier-price" id="modalTierPrice"></div>

    <form id="paymentForm" onsubmit="submitPayment(event)">
      <input type="hidden" id="paymentAmount" name="amount">

      <label for="payerName">שם מלא *</label>
      <input type="text" id="payerName" name="name" required placeholder="ישראל ישראלי" autocomplete="name">

      <label for="payerEmail">דוא"ל *</label>
      <input type="email" id="payerEmail" name="email" required placeholder="you@example.com" autocomplete="email" dir="ltr">

      <label for="payerPhone">טלפון</label>
      <input type="tel" id="payerPhone" name="phone" placeholder="050-0000000" autocomplete="tel" dir="ltr">

      <button type="submit" class="modal-submit" id="payBtn">המשך לתשלום →</button>
      <div class="modal-error" id="modalError"></div>
    </form>
  </div>
</div>

<script>
// ── TIER DATA ─────────────────────────────────────────
const tiers = <?= json_encode(array_map(fn($t) => [
  'id'    => $t['id'],
  'name'  => $t['name'],
  'price' => $t['price'],
], $tiers), JSON_UNESCAPED_UNICODE) ?>;

// ── PROGRESS BAR ANIMATION ────────────────────────────
(function() {
  const fill = document.getElementById('progress-fill');
  if (!fill) return;
  const target = parseFloat(fill.dataset.percent || 0);
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        setTimeout(() => { fill.style.width = target + '%'; }, 150);
        observer.disconnect();
      }
    });
  }, { threshold: .3 });
  observer.observe(fill);
})();

// ── GALLERY AUTO-SCROLL ───────────────────────────────
(function() {
  const el = document.getElementById('galleryScroll');
  if (!el) return;
  let dir = 1, paused = false;
  el.addEventListener('mouseenter', () => paused = true);
  el.addEventListener('mouseleave', () => paused = false);
  setInterval(() => {
    if (paused) return;
    el.scrollLeft += dir * 1.2;
    if (el.scrollLeft + el.clientWidth >= el.scrollWidth - 10) dir = -1;
    if (el.scrollLeft <= 0) dir = 1;
  }, 16);
})();

// ── TOAST AUTO-HIDE ───────────────────────────────────
(function() {
  const toast = document.getElementById('toast');
  if (!toast) return;
  setTimeout(() => { toast.classList.remove('show'); }, 6000);
})();

// ── MODAL ─────────────────────────────────────────────
function openModal(tierId) {
  const tier = tiers.find(t => t.id === tierId);
  if (!tier) return;
  document.getElementById('modalTierName').textContent  = tier.name;
  document.getElementById('modalTierPrice').textContent = '₪' + tier.price.toLocaleString('he-IL');
  document.getElementById('paymentAmount').value        = tier.price;
  document.getElementById('modalError').textContent     = '';
  document.getElementById('payBtn').disabled            = false;
  document.getElementById('payBtn').textContent         = 'המשך לתשלום →';
  document.getElementById('modalBackdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
  setTimeout(() => document.getElementById('payerName').focus(), 200);
}

function closeModal() {
  document.getElementById('modalBackdrop').classList.remove('open');
  document.body.style.overflow = '';
}

document.getElementById('modalBackdrop').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

// ── PAYMENT SUBMIT ────────────────────────────────────
async function submitPayment(e) {
  e.preventDefault();
  const btn = document.getElementById('payBtn');
  const err = document.getElementById('modalError');
  err.textContent = '';
  btn.disabled = true;
  btn.textContent = 'מתחבר למערכת התשלום...';

  const form = e.target;
  const body = new FormData(form);
  body.append('action', 'create_payment');

  try {
    const res  = await fetch(window.location.pathname, { method: 'POST', body });
    const data = await res.json();
    if (data.url) {
      window.location.href = data.url;
    } else {
      err.textContent = data.error || 'אירעה שגיאה. נסה שוב.';
      btn.disabled = false;
      btn.textContent = 'המשך לתשלום →';
    }
  } catch (ex) {
    err.textContent = 'בעיית חיבור. בדוק את האינטרנט ונסה שוב.';
    btn.disabled = false;
    btn.textContent = 'המשך לתשלום →';
  }
}
</script>
</body>
</html>
