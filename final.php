<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>智慧讀書排程系統</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

<style>
:root{
  --text-color:#000000;
}

/* 套用到所有文字 */
body{
  color:var(--text-color);
}

h1,h2,h3,h4,h5,h6,
label,span,div,p,td,th,a{
  color:var(--text-color) !important;
}
body{
  font-family:"Microsoft JhengHei";
  background:#f5f7fb;
  background-size:cover;
  background-position:center;
  transition:0.3s;
}
.dark{
  background:#0f172a !important;
  color:white;
}
.card{
  border-radius:16px;
  background:rgba(255,255,255,0.8);
  backdrop-filter:blur(10px);
}
.dark .card{
  background:rgba(30,41,59,0.7);
}
#bgCanvas{
  position:fixed;
  top:0;
  left:0;
  width:100%;
  height:100%;
  z-index:0;
}
.container{
  position:relative;
  z-index:1;
}
#panel{
  position:fixed;
  top:20px;
  right:20px;
  width:260px;
  z-index:2;
}
#panel.collapsed{
  height:50px;
  overflow:hidden;
}
#clockPanel{
  position:fixed;
  top:20px;
  left:20px;
  width:260px;
  z-index:2;
}

#clockPanel.collapsed{
  height:50px;
  overflow:hidden;
}

#motogpPanel{
  position:fixed;
  top:240px;
  left:20px;
  width:260px;
  z-index:2;
}
</style>
</head>

<body>

<canvas id="bgCanvas"></canvas>

<div id="panel" class="card p-3">
<div class="d-flex justify-content-between">
<b>🎛️ 設定</b>
<button class="btn btn-sm btn-secondary" onclick="togglePanel()">－</button>
</div>


<hr>

<button class="btn btn-dark w-100 mb-2" onclick="toggleDark()">🌙 深色模式</button>

<select class="form-select mb-2" onchange="setPreset(this.value)">
<option value="">選擇背景</option>
<option value="space">🌌 星空</option>
<option value="sea">🌊 海洋</option>
<option value="city">🌃 夜景</option>
<option value="study">📚 書房</option>
</select>

<button class="btn btn-outline-primary w-100 mb-2" onclick="toggleParticles()">✨ 粒子開關</button>
<label>🅰️ 文字顏色</label>
<input type="color" id="textColor" class="form-control form-control-color mb-2" value="#000000">
<button class="btn btn-outline-secondary w-100 mb-2" onclick="applyTextColor()">套用文字顏色</button>
</div>

<div id="clockPanel" class="card p-3">
  <div class="d-flex justify-content-between align-items-center">
    <b>⏰ 時鐘</b>
    <button class="btn btn-sm btn-secondary" onclick="toggleClock()">－</button>
  </div>
  <hr>
  <h4 id="clockTime" class="text-center">--:--:--</h4>
  <div id="clockDate" class="text-center"></div>
</div>

<div id="motogpPanel" class="card p-3">
  <b>🏁 MotoGP 即時資料（API）</b>
  <hr>

  <div class="mb-2">
    <small>⏳ 倒數下一場</small>
    <h6 id="countdown">載入中...</h6>
  </div>

  <div class="mb-2">
    <small>📅 下一場賽事</small>
    <div id="nextRace"></div>
  </div>

  <img id="raceImg" class="img-fluid rounded mb-2"/>

  <div>
    <small>🏆 排名</small>
    <div id="ranking"></div>
  </div>
</div>
<div class="container py-5" style="max-width:1200px">

<h2 class="text-center mb-4" color="#ffffff">📚 智慧讀書排程系統</h2>

<!-- 基本設定 -->
<div class="card p-4 mb-4">
<h5>① 基本設定</h5>
<div class="row g-3">

<div class="col-md-3">
<label>考試日期</label>
<input type="date" id="examDate" class="form-control">
</div>

<div class="col-md-3">
<label>每日讀書時數</label>
<input type="number" id="dailyHours" class="form-control" value="6">
</div>

<div class="col-md-3">
<label>開始時間</label>
<input type="time" id="startTime" class="form-control" value="09:00">
</div>

<div class="col-md-3">
<label>結束時間</label>
<input type="time" id="endTime" class="form-control" value="22:00">
</div>

<div class="col-md-4">
<label>考試類別</label>
<select id="examType" class="form-select" onchange="loadSubjectsByExam()">
<option value="">請選擇</option>
<option value="law">法律類</option>
<option value="engineering">電機類</option>
<option value="accounting">財稅會計類</option>
<option value="police">警察特考</option>
<option value="admin">一般行政類</option>
<option value="it">資訊類</option>
</select>
</div>

</div>
</div>

<!-- 科目 -->
<div class="card p-4 mb-4">
<h5>② 科目設定</h5>
<div id="subjectInputs" class="row g-3"></div>
<button class="btn btn-primary mt-3" onclick="addSubject()">＋ 新增科目</button>
</div>

<div class="text-center mb-4">
  <button class="btn btn-success btn-lg" onclick="generatePlan()">產生智慧課表</button>
  <button class="btn btn-outline-primary btn-lg" onclick="exportPDF()">匯出PDF</button>
  <button class="btn btn-outline-warning btn-lg" onclick="exportICS()">匯出行事曆</button>
</div>

<!-- 圓餅圖 -->
<div class="card p-4 mb-4">
<h5>③ 科目比例</h5>
<canvas id="pieChart"></canvas>
</div>

<!-- 課表 -->
<div class="card p-4">
<h5>④ 每日詳細排程（2小時區塊）</h5>
<div id="schedule"></div>
</div>

</div>

<script>
let pieChart=null;
let generatedData=[];
let particlesEnabled=true;

function toggleDark(){
 document.body.classList.toggle("dark");
}

/* 🎛️ 面板 */
function togglePanel(){
 document.getElementById("panel").classList.toggle("collapsed");
}

/* 🌌 背景 */
function setPreset(type){
 let url="";
 if(type==="space") url="https://images.unsplash.com/photo-1462331940025-496dfbfc7564";
 if(type==="sea") url="https://images.unsplash.com/photo-1507525428034-b723cf961d3e";
 if(type==="city") url="https://images.unsplash.com/photo-1499346030926-9a72daac6c63";
 if(type==="study") url="https://images.unsplash.com/photo-1519681393784-d120267933ba";
 document.body.style.backgroundImage=`url(${url})`;
}
function applyTextColor(){
 const color=document.getElementById("textColor").value;
 document.documentElement.style.setProperty('--text-color', color);
 localStorage.setItem("textColor", color);
}

window.addEventListener("load", ()=>{
 const savedColor = localStorage.getItem("textColor");
 if(savedColor){
  document.documentElement.style.setProperty('--text-color', savedColor);
  document.getElementById("textColor").value = savedColor;
 }
});

function updateClock(){
  const now = new Date();

  const h = now.getHours().toString().padStart(2,'0');
  const m = now.getMinutes().toString().padStart(2,'0');
  const s = now.getSeconds().toString().padStart(2,'0');

  document.getElementById("clockTime").innerText = `${h}:${m}:${s}`;
  document.getElementById("clockDate").innerText = now.toLocaleDateString();
}

setInterval(updateClock,1000);
updateClock();

function toggleClock(){
  document.getElementById("clockPanel").classList.toggle("collapsed");
}

let racesData = [];
let standingsData = [];

async function initMotoGP(){
  racesData = await API.getRaces();
  standingsData = await API.getStandings();

  renderNextRace();
  renderRanking();
  updateCountdown();

  setInterval(updateCountdown, 60000);
}

/* 下一場 */
function getNextRace(){
  const now = new Date();
  return racesData.find(r => new Date(r.date) > now);
}

/* 倒數 */
function updateCountdown(){
  const race = getNextRace();
  if(!race) return;

  const diff = new Date(race.date) - new Date();

  const days = Math.floor(diff/(1000*60*60*24));
  const hours = Math.floor((diff/(1000*60*60))%24);
  const mins = Math.floor((diff/(1000*60))%60);

  document.getElementById("countdown").innerText =
    `${days}天 ${hours}時 ${mins}分`;
}

/* 下一場 */
function renderNextRace(){
  const race = getNextRace();
  if(!race) return;

  document.getElementById("nextRace").innerHTML = `
    <b>${race.name}</b><br>
    📍 ${race.location}<br>
    📅 ${race.date}
  `;

  document.getElementById("raceImg").src = race.img;
}

/* 排名 */
function renderRanking(){
  let html = "<ol style='padding-left:15px; margin:0'>";
  standingsData.forEach(r=>{
    html += `<li>${r.name} (${r.points})</li>`;
  });
  html += "</ol>";

  document.getElementById("ranking").innerHTML = html;
}

initMotoGP();

/* ===== API 層（可替換） ===== */
const API = {
  async getRaces(){
    // 👉 未來可換真 API
    return [
      { name:"Qatar GP", date:"2026-04-20", location:"Lusail", img:"https://images.unsplash.com/photo-1508609349937-5ec4ae374ebf" },
      { name:"Spain GP", date:"2026-05-05", location:"Jerez", img:"https://images.unsplash.com/photo-1521412644187-c49fa049e84d" }
    ];
  },

  async getStandings(){
    // 👉 未來可換真 API
    return [
      { name:"Pecco Bagnaia", points:120 },
      { name:"Jorge Martin", points:110 },
      { name:"Marc Marquez", points:98 },
      { name:"Fabio Quartararo", points:85 },
      { name:"Enea Bastianini", points:80 }
    ];
  }
};
/* 國考科目 */
const examSubjectsData = {
  law: [
    { name: "憲法", type: "memory" },
    { name: "行政法", type: "memory" },
    { name: "民法", type: "memory" },
    { name: "刑法", type: "memory" },
    { name: "法學緒論", type: "memory" }
  ],
  engineering: [
    { name: "電路學", type: "logic" },
    { name: "電子學", type: "logic" },
    { name: "電磁學", type: "logic" },
    { name: "工程數學", type: "logic" }
  ],
  accounting: [
    { name: "會計學", type: "logic" },
    { name: "成本與管理會計", type: "logic" },
    { name: "審計學", type: "logic" },
    { name: "稅務法規", type: "memory" }
  ],
  police: [
    { name: "刑法概要", type: "memory" },
    { name: "警察法規", type: "memory" },
    { name: "犯罪學", type: "memory" },
    { name: "英文", type: "memory" }
  ],
  admin: [
    { name: "行政學", type: "memory" },
    { name: "政治學", type: "memory" },
    { name: "公共管理", type: "memory" },
    { name: "英文", type: "memory" }
  ],
  it: [
    { name: "資料結構", type: "logic" },
    { name: "演算法", type: "logic" },
    { name: "計算機概論", type: "memory" },
    { name: "程式設計", type: "logic" }
  ]
};

function addSubjectWithData(name="", type="memory"){
  const container=document.getElementById('subjectInputs');
  const i=container.children.length+1;

  const div=document.createElement('div');
  div.className='col-md-4';

  div.innerHTML=`
    <div class="card p-3">
      <input type="text" id="name${i}" class="form-control mb-2" value="${name}">
      <select id="type${i}" class="form-select mb-2">
        <option value="memory" ${type==="memory"?"selected":""}>記憶型</option>
        <option value="logic" ${type==="logic"?"selected":""}>理解型</option>
      </select>
      <input type="number" id="weight${i}" class="form-control mb-2" value="3">
      <button class="btn btn-danger btn-sm" onclick="this.parentElement.parentElement.remove()">刪除</button>
    </div>
  `;
  container.appendChild(div);
}

function addSubject(){ addSubjectWithData(); }

function loadSubjectsByExam(){
  const type=document.getElementById('examType').value;
  const container=document.getElementById('subjectInputs');
  container.innerHTML="";
  if(!type) return;
  examSubjectsData[type].forEach(s=>addSubjectWithData(s.name,s.type));
}

function timeToMinutes(t){
  const [h,m]=t.split(":").map(Number);
  return h*60+m;
}

function minutesToTime(min){
  const h=Math.floor(min/60).toString().padStart(2,'0');
  const m=(min%60).toString().padStart(2,'0');
  return `${h}:${m}`;
}

function daysBetween(d1,d2){
  return Math.ceil((d2-d1)/(1000*60*60*24));
}

function generatePlan(){

 generatedData = [];

 const exam=new Date(document.getElementById('examDate').value);
 const today=new Date();
 const days=daysBetween(today,exam);

 const startMin=timeToMinutes(document.getElementById('startTime').value);
 const endMin=timeToMinutes(document.getElementById('endTime').value);

 if(days<=0){
  alert("請輸入正確考試日期");
  return;
 }

 const container=document.getElementById('subjectInputs');
 const count=container.children.length;

 if(count==0){
  alert("請新增科目");
  return;
 }

 let subjects=[];
 for(let i=1;i<=count;i++){
  const name=document.getElementById('name'+i)?.value || '科目'+i;
  const type=document.getElementById('type'+i)?.value;
  const weight=parseFloat(document.getElementById('weight'+i)?.value) || 1;
  subjects.push({name,type,weight});
 }

 if(pieChart) pieChart.destroy();

 pieChart=new Chart(document.getElementById('pieChart'),{
  type:'pie',
  data:{
    labels:subjects.map(s=>s.name),
    datasets:[{data:subjects.map(s=>s.weight)}]
  }
 });

 let scheduleHTML='';

 for(let d=0; d<days; d++){

  const date=new Date();
  date.setDate(today.getDate()+d+1);
  const ds=date.toISOString().slice(0,10);

  let currentMin=startMin;
  let rows='';

  let subjectIndex = d % subjects.length;
  const blockDuration = 120;

  while(currentMin < endMin){

    const lunchStart = 12 * 60;
    const lunchEnd = 13 * 60;

    if(currentMin >= lunchStart && currentMin < lunchEnd){
      rows += `<tr class="table-info"><td>${minutesToTime(lunchStart)}-${minutesToTime(lunchEnd)}</td><td>🍱 午休</td><td>1.0h</td></tr>`;
      currentMin = lunchEnd;
      continue;
    }

    const s = subjects[subjectIndex % subjects.length];

    let duration = blockDuration;

    if(currentMin + duration > endMin) duration = endMin - currentMin;
    if(currentMin < lunchStart && currentMin + duration > lunchStart) duration = lunchStart - currentMin;
    if(duration <= 0) break;

    const start = minutesToTime(currentMin);
    const end = minutesToTime(currentMin + duration);

    rows += `<tr><td>${start}-${end}</td><td>📖 ${s.name}</td><td>${(duration/60).toFixed(1)}h</td></tr>`;

    generatedData.push({date:ds,start,end,subject:s.name});

    currentMin += duration;
    subjectIndex++;
  }

  scheduleHTML+=`
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">📅 ${ds}</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <thead class="table-light">
            <tr><th>時間</th><th>科目</th><th>時數</th></tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
    </div>
  `;
 }

 document.getElementById('schedule').innerHTML=scheduleHTML;
}

/* PDF */
function exportPDF(){
 if(generatedData.length===0){
  alert("請先產生課表");
  return;
 }

 const { jsPDF } = window.jspdf;
 const doc = new jsPDF();

 let y=10;
 doc.text("智慧讀書排程",10,y);
 y+=10;

 generatedData.forEach(item=>{
   doc.text(`${item.date} ${item.start}-${item.end} ${item.subject}`,10,y);
   y+=8;
   if(y>280){
     doc.addPage();
     y=10;
   }
 });

 doc.save("schedule.pdf");
}

/* ICS */
function exportICS(){
 if(generatedData.length===0){
  alert("請先產生課表");
  return;
 }

 let ics="BEGIN:VCALENDAR\nVERSION:2.0\n";

 generatedData.forEach(item=>{
   const start=item.date.replace(/-/g,'')+"T"+item.start.replace(':','')+"00";
   const end=item.date.replace(/-/g,'')+"T"+item.end.replace(':','')+"00";

   ics+="BEGIN:VEVENT\n";
   ics+=`DTSTART:${start}\n`;
   ics+=`DTEND:${end}\n`;
   ics+=`SUMMARY:${item.subject}\n`;
   ics+="END:VEVENT\n";
 });

 ics+="END:VCALENDAR";

 const blob=new Blob([ics],{type:'text/calendar'});
 const a=document.createElement('a');
 a.href=URL.createObjectURL(blob);
 a.download="schedule.ics";
 a.click();
}

</script>

</body>
</html>