<?php
// 1. Web App URL ของคุณที่ได้จากการ Deploy
$webAppUrl = "https://script.google.com/macros/s/AKfycbzlJCgVtTH0bD7nJsxXobzk_gcOXWnByU1rI4RkkwPOYwPELXpZ8_6OBaN5qBISybfq2w/exec";

// 2. ดึงข้อมูลจาก Google Apps Script API
$jsonData = @file_get_contents($webAppUrl);

if ($jsonData === FALSE) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'>❌ ไม่สามารถดึงข้อมูลจาก API ได้ กรุณาตรวจสอบ URL หรือสิทธิ์การเข้าถึง</div>");
}

// แปลง JSON เป็น PHP Array
$data = json_decode($jsonData, true);
$targetData = isset($data['target']) ? $data['target'] : [];

// เตรียมตัวแปรสำหรับเก็บข้อมูลที่จะเอาไปทำกราฟและคำนวณ KPI
$labels = [];
$stocks = [];
$sells = [];
$passedCount = 0;
$totalUnits = 0;

$overviewRow = null; // แยกแถวภาพรวมไว้ต่างหาก
$filteredTargetData = []; // เก็บเฉพาะหน่วยงานย่อย

foreach ($targetData as $row) {
    // ตรวจสอบว่าเป็นแถวสรุปภาพรวมหรือไม่
    if (strpos($row['organization'], 'ภาพรวม') !== false) {
        $overviewRow = $row;
        continue; // ข้ามตัวนี้ไป ไม่เอาไปวาดในกราฟแท่งหน่วยงานย่อย
    }

    $labels[] = $row['organization'];
    $stocks[] = intval($row['number_stock']);
    $sells[] = intval($row['sell_number']);
    
    if ($row['target_30_percent'] === 'ผ่าน') {
        $passedCount++;
    }
    $totalUnits++;
    $filteredTargetData[] = $row;
}

// คำนวณยอดรวม (ถ้าไม่มีแถวภาพรวมส่งมา จะคำนวณจากหน่วยงานย่อยให้เอง)
$totalStock = $overviewRow ? $overviewRow['number_stock'] : array_sum($stocks);
$totalSell = $overviewRow ? $overviewRow['sell_number'] : array_sum($sells);
$totalPercentage = $overviewRow ? $overviewRow['sell_percentage'] : ($totalStock > 0 ? ($totalSell / $totalStock) * 100 : 0);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PEA TARGET PERFORMANCE DASHBOARD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap');
        body { font-family: 'Kanit', sans-serif; }
        .pea-gradient { background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 100%); }
        .gold-text { color: #fbbf24; text-shadow: 0 0 10px rgba(251, 191, 36, 0.5); }
        .card-hover:hover { transform: translateY(-5px) scale(1.01); }
        
        /* สไตล์พิเศษสำหรับหน้า Loading */
        .loading-gradient { background: linear-gradient(180deg, #2e1065 0%, #4c1d95 50%, #6d28d9 100%); }
        .gold-glow { box-shadow: 0 0 20px rgba(251, 191, 36, 0.6); }
        
        @keyframes pulseGlow {
            0%, 100% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.1); opacity: 1; }
        }
        .pulse-layer { animation: pulseGlow 2s infinite ease-in-out; }
        .no-scroll { overflow: hidden; }
    </style>
</head>
<body class="bg-slate-50 no-scroll" 
      x-data="{ open: false, activeItem: {}, isLoading: true, touchStartY: 0 }"
      :class="{ 'no-scroll': open || isLoading }"
      x-init="setTimeout(() => { isLoading = false; }, 2500)">

<!-- 1. หน้าจอ Loading Screen -->
<div x-show="isLoading"
     x-transition:leave="transition ease-in duration-700 transform"
     x-transition:leave-start="translate-y-0 opacity-100"
     x-transition:leave-end="-translate-y-full opacity-0"
     @touchstart="touchStartY = $event.touches[0].clientY"
     @touchend="if (touchStartY - $event.changedTouches[0].clientY > 70) { isLoading = false; }"
     class="fixed inset-0 z-[100] loading-gradient flex flex-col justify-between items-center p-8 select-none text-center overflow-hidden">
    
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20 pointer-events-none"></div>
    <div></div>
    <div class="relative z-10 flex flex-col items-center">
        <div class="w-24 h-24 sm:w-28 sm:h-28 bg-amber-500/10 rounded-full absolute pulse-layer flex items-center justify-center blur-md"></div>
        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-3xl flex items-center justify-center text-4xl sm:text-5xl shadow-lg gold-glow animate__animated animate__bounceIn">
            📊
        </div>
        <h1 class="text-white text-4xl sm:text-5xl font-bold tracking-tighter mt-6 animate__animated animate__fadeInUp animate__delay-1s">
            PEA <span class="gold-text">PERFORMANCE</span> APP
        </h1>
        <p class="text-purple-200 mt-3 text-base sm:text-lg max-w-xs font-light tracking-wide animate__animated animate__fadeInUp animate__delay-1s">
            ระบบรายงานผลการขาย กฟฉ.3<br><span class="text-yellow-400/90 font-medium">"มุ่งสู่เป้าหมาย 30% อย่างแม่นยำ"</span>
        </p>
        <div class="mt-8 flex gap-1.5 animate__animated animate__fadeIn animate__delay-2s">
            <span class="w-3 h-3 bg-amber-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></span>
            <span class="w-3 h-3 bg-amber-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
            <span class="w-3 h-3 bg-amber-400 rounded-full animate-bounce" style="animation-delay: 0.3s"></span>
        </div>
    </div>
    <div class="relative z-10 flex flex-col items-center mb-4 animate__animated animate__fadeInUp animate__delay-2s pointer-events-none">
        <div class="text-amber-400 text-xl font-bold mb-1 animate-bounce">▲</div>
        <p class="text-purple-300 text-xs sm:text-sm tracking-widest font-light uppercase">Swipe Up to Skip หรือรอสักครู่</p>
    </div>
</div>

<!-- 2. ส่วนหัวของเว็บแอพ (Header) -->
<header class="pea-gradient p-6 sm:p-10 shadow-2xl mb-6 sm:mb-10 relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-20"></div>
    <div class="container mx-auto text-center relative z-10">
        <h1 class="text-white text-3xl sm:text-5xl font-bold tracking-tighter">PEA <span class="gold-text">TARGET</span> PERFORMANCE</h1>
        <p class="text-purple-200 mt-2 text-sm sm:text-lg">รายงานผลการดำเนินงานเป้าหมาย 30% แยกตามหน่วยงาน ⚡️ กฟฉ.3</p>
        <!-- เพิ่มป้ายแสดงข้อมูลช่วงเวลา -->
        <div class="inline-block mt-3 px-4 py-1.5 bg-black/30 rounded-full text-xs text-yellow-300 font-medium tracking-wide backdrop-blur-xs">
            📅 เป้าหมายพัสดุ: <span class="text-white">สิ้นปี ธ.ค. 2568</span> | ยอดจำหน่ายสะสม: <span class="text-white">ม.ค. 2569 - ปัจจุบัน</span>
        </div>
    </div>
</header>

<main class="container mx-auto px-4 pb-20">
    <!-- 3. ส่วนของ Cards สรุปตัวเลขผลงานรวม (KPI Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
        <div class="bg-white p-5 rounded-3xl shadow-md border border-purple-50 border-l-4 border-purple-700 card-hover transition-all duration-300">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">สต็อกเป้าหมาย (ธ.ค. 68)</h3>
            <p class="text-2xl sm:text-3xl font-bold text-purple-950 mt-2"><?php echo number_format($totalStock); ?> <span class="text-xs font-normal text-gray-400">หน่วย</span></p>
        </div>
        <div class="bg-white p-5 rounded-3xl shadow-md border border-purple-50 border-l-4 border-emerald-500 card-hover transition-all duration-300">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">ยอดจำหน่ายจริง (ม.ค. 69 - ปัจจุบัน)</h3>
            <p class="text-2xl sm:text-3xl font-bold text-emerald-600 mt-2"><?php echo number_format($totalSell); ?> <span class="text-xs font-normal text-gray-400">หน่วย</span></p>
        </div>
        <div class="bg-white p-5 rounded-3xl shadow-md border border-purple-50 border-l-4 border-amber-500 card-hover transition-all duration-300">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">เปอร์เซ็นต์ยอดขายรวม</h3>
            <p class="text-2xl sm:text-3xl font-bold text-amber-500 mt-2"><?php echo number_format($totalPercentage, 2); ?>%</p>
        </div>
        <div class="bg-white p-5 rounded-3xl shadow-md border border-purple-50 border-l-4 border-yellow-500 card-hover transition-all duration-300">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">ผ่านเกณฑ์ (Target 30%)</h3>
            <p class="text-2xl sm:text-3xl font-bold text-purple-900 mt-2"><?php echo $passedCount; ?> <span class="text-sm font-medium text-gray-400">/ <?php echo $totalUnits; ?> หน่วยงาน</span></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8 items-start">
        <!-- 4. ส่วนแสดงผลกราฟแท่งแบบละเอียด -->
        <div class="lg:col-span-2 bg-white p-5 sm:p-6 rounded-3xl shadow-lg border border-purple-50">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-purple-900">📊 กราฟเปรียบเทียบ จำนวนสต็อกเป้าหมาย vs ยอดขายสะสม</h2>
                <p class="text-xs text-gray-400">เปรียบเทียบสต็อกเป้าหมายสิ้นปี 68 กับยอดขาย ม.ค. 69 - ปัจจุบัน</p>
            </div>
            <div class="relative h-[320px] sm:h-[400px]">
                <canvas id="targetChart"></canvas>
            </div>
        </div>

        <!-- 5. ส่วนตารางสรุปรายหน่วยงานย่อย -->
        <div class="bg-white rounded-3xl shadow-lg border border-purple-50 overflow-hidden">
            <div class="p-5 border-b border-purple-50">
                <h2 class="text-lg font-bold text-purple-900">🏢 สรุปข้อมูลรายหน่วยงาน</h2>
                <p class="text-xs text-gray-400">คลิกที่แถวเพื่อดูข้อมูลเชิงลึกแบบการ์ด</p>
            </div>
            <div class="max-h-[400px] overflow-y-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-slate-50 text-purple-950 text-xs font-bold uppercase border-b border-purple-100">
                        <tr>
                            <th class="py-3 px-4">หน่วยงาน</th>
                            <th class="py-3 px-2 text-right">สต็อก / ขายได้</th>
                            <th class="py-3 px-4 text-center">เป้า 30%</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-purple-50">
                        <?php foreach ($filteredTargetData as $row): 
                            $isPassed = ($row['target_30_percent'] === 'ผ่าน');
                            $rowBg = $isPassed ? 'bg-emerald-50/40 hover:bg-emerald-50' : 'hover:bg-purple-50/50';
                            $row_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr class="<?php echo $rowBg; ?> cursor-pointer transition-colors" 
                                @click="open = true; activeItem = <?php echo $row_json; ?>">
                                <td class="py-3 px-4 font-medium text-gray-800 line-clamp-1 mt-1"><?php echo htmlspecialchars($row['organization']); ?></td>
                                <td class="py-3 px-2 text-right text-xs whitespace-nowrap">
                                    <span class="text-gray-600"><?php echo number_format($row['number_stock']); ?></span>
                                    <span class="text-gray-300">/</span>
                                    <span class="text-purple-700 font-bold"><?php echo number_format($row['sell_number']); ?></span>
                                    <span class="text-gray-400 ml-1">(<?php echo number_format($row['sell_percentage'], 1); ?>%)</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($isPassed): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">ผ่าน</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-400">ไม่ผ่าน</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- แสดงแถวสรุปภาพรวมไว้ด้านล่างสุดของตาราง -->
            <?php if ($overviewRow): ?>
                <div class="pea-gradient p-4 text-white flex justify-between items-center text-sm font-bold">
                    <span>⚡️ <?php echo htmlspecialchars($overviewRow['organization']); ?></span>
                    <div class="text-right">
                        <span><?php echo number_format($overviewRow['number_stock']); ?> / <?php echo number_format($overviewRow['sell_number']); ?></span>
                        <span class="text-amber-300 ml-1">(<?php echo number_format($overviewRow['sell_percentage'], 2); ?>%)</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- 6. Modal Popup รายละเอียดของแต่ละหน่วยงาน (Alpine.js) -->
<div x-show="open" class="fixed inset-0 z-50 flex items-end md:items-center justify-center p-0 md:p-4 bg-black/60 backdrop-blur-sm" x-cloak>
    <div class="bg-white rounded-t-[2rem] md:rounded-[2rem] w-full max-w-lg md:max-w-md p-6 relative shadow-[0_-10px_30px_rgba(0,0,0,0.15)] md:shadow-[0_20px_50px_rgba(0,0,0,0.25)] max-h-[90vh] md:max-h-[85vh] overflow-y-auto animate__animated animate__fadeInUp animate__fast" @click.away="open = false">
        
        <button @click="open = false" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition text-3xl z-10 p-2">&times;</button>
        
        <div class="text-center mb-5 mt-2">
            <div class="w-12 h-12 bg-purple-100 text-purple-700 rounded-full flex items-center justify-center text-xl mx-auto mb-2">🏢</div>
            <h2 class="text-xl font-bold text-purple-900 px-4" x-text="activeItem.organization"></h2>
            <p class="text-xs text-gray-400 mt-0.5">ข้อมูลผลการดำเนินงานรายหน่วยงาน</p>
        </div>
        
        <div class="space-y-3 text-gray-700 bg-slate-50 p-5 rounded-2xl border border-slate-100 text-sm mb-4">
            <div class="flex justify-between border-b border-gray-100 pb-2">
                <span class="text-gray-500">สต็อกเป้าหมาย (สิ้นปี 2568):</span>
                <span x-text="Number(activeItem.number_stock).toLocaleString() + ' หน่วย'" class="font-semibold text-gray-800"></span>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-2">
                <span class="text-gray-500">ยอดจำหน่ายสะสม (ม.ค. 69 - ปัจจุบัน):</span>
                <span x-text="Number(activeItem.sell_number).toLocaleString() + ' หน่วย'" class="font-bold text-emerald-600"></span>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-2">
                <span class="text-gray-500">เปอร์เซ็นต์ความสำเร็จ:</span>
                <span x-text="Number(activeItem.sell_percentage).toFixed(2) + '%'" class="font-bold text-amber-500"></span>
            </div>
            <div class="flex justify-between pt-1">
                <span class="text-gray-500 font-medium">การประเมินเป้าหมาย 30%:</span>
                <template x-if="activeItem.target_30_percent === 'ผ่าน'">
                    <span class="bg-emerald-100 text-emerald-800 px-3 py-0.5 rounded-full text-xs font-bold">✨ ผ่านเกณฑ์ขั้นต่ำ</span>
                </template>
                <template x-if="activeItem.target_30_percent !== 'ผ่าน'">
                    <span class="bg-red-50 text-red-600 px-3 py-0.5 rounded-full text-xs font-bold">❌ ยังไม่ผ่านเกณฑ์</span>
                </template>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 p-4 rounded-2xl border border-purple-100 text-xs sm:text-sm mb-2">
            <h4 class="text-purple-900 font-bold text-sm mb-2 flex items-center gap-1.5">⚡️ บทวิเคราะห์ช่วงเวลาผลงาน</h4>
            <p class="text-gray-600 leading-relaxed">
                หน่วยงานนี้ทำยอดขายสะสมตั้งแต่ <span class="font-semibold text-purple-950">ม.ค. 2569 เป็นต้นมา</span> ได้เป็นสัดส่วน <span class="font-bold text-purple-700" x-text="Number(activeItem.sell_percentage).toFixed(2) + '%'"></span> 
                เมื่อเปรียบเทียบกับจำนวนสต็อกที่เป็นเป้าหมายคงค้างของ <span class="font-semibold text-purple-950">สิ้นปี ธ.ค. 2568</span> จำนวน <span class="font-bold text-gray-800" x-text="Number(activeItem.number_stock).toLocaleString()"></span> หน่วย
            </p>
        </div>

        <button @click="open = false" class="w-full mt-4 py-3 bg-gradient-to-r from-purple-800 to-purple-600 text-white rounded-xl font-bold text-sm shadow-md hover:scale-[1.01] active:scale-95 transition-all text-center">
            ปิดหน้าต่างนี้
        </button>
    </div>
</div>

<!-- 7. สคริปต์ควบคุมตัวกราฟ Chart.js -->
<script>
    const ctx = document.getElementById('targetChart').getContext('2d');
    
    const labelsData = <?php echo json_encode($labels); ?>;
    const stocksData = <?php echo json_encode($stocks); ?>;
    const sellsData = <?php echo json_encode($sells); ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labelsData,
            datasets: [
                {
                    label: 'สต็อกเป้าหมาย (สิ้นปี 68)',
                    data: stocksData,
                    backgroundColor: 'rgba(109, 40, 217, 0.45)',
                    borderColor: 'rgb(109, 40, 217)',
                    borderWidth: 1.5,
                    borderRadius: 6
                },
                {
                    label: 'ยอดจำหน่ายสะสม (ม.ค. 69 - ปัจจุบัน)',
                    data: sellsData,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1.5,
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { family: 'Kanit', size: 12 } }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Kanit', size: 10 }, maxRotation: 45, minRotation: 45 }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { family: 'Kanit', size: 11 } }
                }
            }
        }
    });
</script>

</body>
</html>