<?php
// 1. ใส่ Web App URL ที่คุณได้จากการ Deploy
$webAppUrl = "https://script.google.com/macros/s/AKfycbzlJCgVtTH0bD7nJsxXobzk_gcOXWnByU1rI4RkkwPOYwPELXpZ8_6OBaN5qBISybfq2w/exec";

// 2. ดึงข้อมูลจาก Google Apps Script API
$jsonData = file_get_contents($webAppUrl);

if ($jsonData === FALSE) {
    die("ไม่สามารถดึงข้อมูลจาก API ได้ กรุณาตรวจสอบ URL หรือสิทธิ์การเข้าถึง");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กฟฉ.3 - target Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-900">

    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <header class="mb-8 text-center md:text-left">
            <h1 class="text-3xl font-black text-gray-800 tracking-tight">📊 ระบบรายงานผลการขาย กฟฉ.3</h1>
            <p class="text-sm text-gray-500 mt-1">ข้อมูลสถานะยอดขายและเป้าหมาย 30% แยกตามหน่วยงาน</p>
        </header>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100 border-l-4 border-indigo-500">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">จำนวนสต็อกทั้งหมด</h3>
                <p class="text-3xl font-black text-gray-800 mt-2"><?php echo number_format($totalStock); ?> <span class="text-xs font-normal text-gray-400">หน่วย</span></p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100 border-l-4 border-emerald-500">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">ยอดขายได้จริง</h3>
                <p class="text-3xl font-black text-emerald-600 mt-2"><?php echo number_format($totalSell); ?> <span class="text-xs font-normal text-gray-400">หน่วย</span></p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100 border-l-4 border-amber-500">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">เปอร์เซ็นต์ยอดขายรวม</h3>
                <p class="text-3xl font-black text-amber-500 mt-2"><?php echo number_format($totalPercentage, 2); ?>%</p>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-xs border border-gray-100 border-l-4 border-sky-500">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">ผ่านเกณฑ์ (Target 30%)</h3>
                <p class="text-3xl font-black text-sky-600 mt-2"><?php echo $passedCount; ?> <span class="text-sm font-medium text-gray-400">/ <?php echo $totalUnits; ?> หน่วยงาน</span></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-xs border border-gray-100">
                <div class="mb-4">
                    <h2 class="text-lg font-bold text-gray-800">กราฟเปรียบเทียบ จำนวนสต็อก vs ยอดขายจริง</h2>
                    <p class="text-xs text-gray-400">แสดงปริมาณงานของแต่ละหน่วยงานย่อย</p>
                </div>
                <div class="relative h-[400px]">
                    <canvas id="targetChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-lg font-bold text-gray-800">ตารางสรุปรายหน่วยงาน</h2>
                    <p class="text-xs text-gray-400">รายละเอียดแบ่งตามโครงสร้างองค์กร</p>
                </div>
                <div class="max-h-[400px] overflow-y-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-gray-50 text-gray-500 text-xs font-bold uppercase border-b border-gray-100">
                            <tr>
                                <th class="py-3 px-4">หน่วยงาน</th>
                                <th class="py-3 px-2 text-right">สต็อก / ขายได้</th>
                                <th class="py-3 px-4 text-center">เป้า 30%</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            <?php foreach ($targetData as $row): 
                                $isOverview = (strpos($row['organization'], 'ภาพรวม') !== false);
                                $isPassed = ($row['target_30_percent'] === 'ผ่าน');
                                
                                // เลือกสีพื้นหลังของแถว
                                $rowBg = $isOverview ? 'bg-indigo-50 font-bold' : ($isPassed ? 'bg-emerald-50/60' : 'hover:bg-gray-50');
                            ?>
                                <tr class="<?php echo $rowBg; ?>">
                                    <td class="py-3 px-4 font-medium text-gray-800"><?php echo htmlspecialchars($row['organization']); ?></td>
                                    <td class="py-3 px-2 text-right text-xs">
                                        <span class="text-gray-600"><?php echo number_format($row['number_stock']); ?></span>
                                        <span class="text-gray-400">/</span>
                                        <span class="text-emerald-600 font-bold"><?php echo number_format($row['sell_number']); ?></span>
                                        <div class="text-[10px] text-gray-400"><?php echo number_format($row['sell_percentage'], 1); ?>%</div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <?php if ($isPassed): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">ผ่าน</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-400">ไม่ผ่าน</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
        const ctx = document.getElementById('targetChart').getContext('2d');
        
        // รับค่ามาจาก PHP Array
        const labelsData = <?php echo json_encode($labels); ?>;
        const stocksData = <?php echo json_encode($stocks); ?>;
        const sellsData = <?php echo json_encode($sells); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labelsData,
                datasets: [
                    {
                        label: 'จำนวนสต็อก (Number Stock)',
                        data: stocksData,
                        backgroundColor: 'rgba(99, 102, 241, 0.6)', // Indigo
                        borderColor: 'rgb(99, 102, 241)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'ยอดขายได้จริง (Sell Number)',
                        data: sellsData,
                        backgroundColor: 'rgba(16, 185, 129, 0.8)', // Emerald
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { family: 'sans-serif', size: 12 } }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    </script>
</body>
</html>