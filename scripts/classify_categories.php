<?php
// 一次性脚本：按英文释义(+中文释义兜底)里的关键词，给单词打学科分类标签。
// 启发式关键词匹配，不是精确科学分类，但足够帮助按主题分组记忆。
// 'en'       — 完整单词，要求两侧都是单词边界（避免 star 匹配到 starts 里）
// 'en_stems' — 有意的词干前缀匹配（如 astronom 匹配 astronomy/astronomical），只要求左边界
// 'cn'       — 中文关键词，子串匹配
// 用法： php scripts/classify_categories.php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script must be run from the command line.');
}
require __DIR__ . '/../core/bootstrap.php';
$categories = [
    'zoology'      => ['name' => '动物学', 'en' => ['animal','mammal','bird','insect','reptile','fish','wildlife','species','creature','predator','prey','rodent','amphibian','larva','bee','ant','spider','snake','wolf','tiger','lion','monkey','whale','frog','worm','butterfly','beetle','serpent'], 'en_stems' => ['zoolog'], 'cn' => ['动物','鸟类','昆虫','爬行动物','哺乳动物','鱼类']],
    'botany'       => ['name' => '植物学', 'en' => ['plant','tree','flower','leaf','seed','flora','fruit','blossom','shrub','vegetation','herb','fungus','fungi','mold','algae','moss'], 'en_stems' => ['botan'], 'cn' => ['植物','花朵','树木','果实','叶子']],
    'biology'      => ['name' => '生物学', 'en' => ['organism','cell','gene','genetic','evolution','tissue','chromosome','embryo','metabolism','ecosystem','habitat','organ','biological','protein','enzyme','bacteria','virus','microorganism'], 'en_stems' => ['biolog'], 'cn' => ['生物','细胞','基因','进化','生态']],
    'astronomy'    => ['name' => '天文学', 'en' => ['star','planet','galaxy','universe','celestial','orbit','cosmic','telescope','lunar','comet','asteroid','planetary','stellar'], 'en_stems' => ['astronom'], 'cn' => ['天文','行星','宇宙','恒星','星系']],
    'geology'      => ['name' => '地质学', 'en' => ['mineral','volcano','volcanic','sediment','earthquake','fossil','terrain','stratum','magma'], 'en_stems' => ['geolog'], 'cn' => ['地质','岩石','矿物','火山','地震','化石']],
    'chemistry'    => ['name' => '化学', 'en' => ['chemical','compound','molecule','acid','chemistry','element'], 'en_stems' => [], 'cn' => ['化学','化合物','分子']],
    'physics'      => ['name' => '物理学', 'en' => ['physics','energy','particle','gravity','velocity','magnetic','momentum'], 'en_stems' => [], 'cn' => ['物理','能量','引力','磁性']],
    'medicine'     => ['name' => '医学', 'en' => ['disease','medical','symptom','treatment','patient','physician','illness','therapy','surgeon','infection'], 'en_stems' => ['diagnos'], 'cn' => ['疾病','症状','治疗','病人','医生']],
    'psychology'   => ['name' => '心理学', 'en' => ['behavior','emotion','perception','personality'], 'en_stems' => ['psycholog','cognit'], 'cn' => ['心理','情绪','认知']],
    'law'          => ['name' => '法律', 'en' => ['legal','lawsuit','crime','judge','statute','testimony','defendant','verdict','jurisdiction'], 'en_stems' => ['legislat'], 'cn' => ['法律','法庭','诉讼','犯罪','法官','立法']],
    'economics'    => ['name' => '经济与商业', 'en' => ['economic','market','trade','business','financial','commerce','industry','currency','investment','revenue'], 'en_stems' => [], 'cn' => ['经济','市场','贸易','商业','金融']],
    'politics'     => ['name' => '政治', 'en' => ['political','government','policy','election','legislature','diplomat','sovereign','regime'], 'en_stems' => ['democra'], 'cn' => ['政治','政府','政策','选举','外交']],
    'art'          => ['name' => '艺术', 'en' => ['sculpture','artistic','aesthetic','museum'], 'en_stems' => [], 'cn' => ['艺术','绘画','雕塑','美学']],
    'music'        => ['name' => '音乐', 'en' => ['music','melody','instrument','song','rhythm','musical'], 'en_stems' => [], 'cn' => ['音乐','旋律','乐器','节奏']],
    'literature'   => ['name' => '文学与写作', 'en' => ['literary','poem','novel','narrative','poet','literature','prose'], 'en_stems' => [], 'cn' => ['文学','诗歌','小说','叙述']],
    'history'      => ['name' => '历史', 'en' => ['history','ancient','historical','century','civilization'], 'en_stems' => [], 'cn' => ['历史','古代','文明']],
    'architecture' => ['name' => '建筑', 'en' => ['building','architecture','architectural'], 'en_stems' => [], 'cn' => ['建筑','结构','建造']],
    'geography'    => ['name' => '地理', 'en' => ['geography','continent','territory','geographic'], 'en_stems' => [], 'cn' => ['地理','大陆','领土']],
    'military'     => ['name' => '军事', 'en' => ['military','warfare','army','soldier','weapon','battle','combat','troops'], 'en_stems' => [], 'cn' => ['军事','战争','军队','士兵','武器','战斗']],
    'religion'     => ['name' => '宗教', 'en' => ['religious','god','church','faith','worship','sacred','divine','spiritual'], 'en_stems' => [], 'cn' => ['宗教','上帝','教堂','信仰','神圣']],
];

foreach ($categories as $slug => $cat) {
    db_query(
        'INSERT INTO categories (name, slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)',
        [$cat['name'], $slug]
    );
}

db_query('DELETE FROM word_categories');

$total = 0;
foreach ($categories as $slug => $cat) {
    $catRow = db_one('SELECT id FROM categories WHERE slug = ?', [$slug]);
    $catId = $catRow['id'];

    $conds = [];
    $params = [];
    foreach ($cat['en'] as $kw) {
        $conds[] = 'definition_en REGEXP ?';
        $params[] = '\\b' . $kw . '\\b';
    }
    foreach ($cat['en_stems'] as $kw) {
        $conds[] = 'definition_en REGEXP ?';
        $params[] = '\\b' . $kw;
    }
    foreach ($cat['cn'] as $kw) {
        $conds[] = 'translation_cn LIKE ?';
        $params[] = '%' . $kw . '%';
    }

    $sql = 'SELECT id FROM words WHERE ' . implode(' OR ', $conds);
    $matches = db_all($sql, $params);

    foreach ($matches as $w) {
        db_query(
            'INSERT IGNORE INTO word_categories (word_id, category_id) VALUES (?, ?)',
            [$w['id'], $catId]
        );
    }

    echo "{$cat['name']} ({$slug}): " . count($matches) . " 词\n";
    $total += count($matches);
}

echo "总计打标签: $total\n";
