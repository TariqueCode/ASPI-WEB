<!DOCTYPE html>
<html lang="bn" class="scroll-smooth" x-data="founderApp()" x-init="init()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>প্রতিষ্ঠাতা মহোদয়ের পরিচিতি | ASPI</title>
    <meta name="theme-color" content="#020617">
    <link rel="icon" type="image/png" href="assets/images/ASPI-Logo.png">
    <link rel="shortcut icon" type="image/png" href="assets/images/ASPI-Logo.png">
    <link rel="apple-touch-icon" href="assets/images/ASPI-Logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode:'class', theme:{ extend:{ fontFamily:{bn:['Hind Siliguri','sans-serif']}, colors:{brand:{gold:'#facc15'}}}}};
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root { color-scheme: light; }
        .dark { color-scheme: dark; }
        body { font-family:'Hind Siliguri',sans-serif; }
        .glass { background:rgba(255,255,255,.86); backdrop-filter:blur(20px); border:1px solid rgba(148,163,184,.18); }
        .dark .glass { background:rgba(15,23,42,.78); border-color:rgba(255,255,255,.07); }
        .hero-glow { background:radial-gradient(circle at 20% 20%, rgba(250,204,21,.18), transparent 38%), radial-gradient(circle at 80% 30%, rgba(59,130,246,.15), transparent 38%); }
        .founder-header-control{color:#ef4444!important;border:2px solid rgba(239,68,68,.35);background:rgba(255,255,255,.86)} .dark .founder-header-control{background:rgba(15,23,42,.86);border-color:rgba(239,68,68,.45)}
    </style>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 transition-colors duration-300">
<header class="sticky top-0 z-50 bg-white/90 dark:bg-slate-950/90 backdrop-blur-xl border-b border-slate-200/70 dark:border-slate-800">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <a href="index.html" class="flex items-center gap-3 min-w-0">
            <span class="w-12 h-12 rounded-xl bg-white border border-slate-200 dark:border-slate-700 shadow-sm p-1 shrink-0"><img src="assets/images/ASPI-Logo.png" alt="ASPI" class="w-full h-full object-contain"></span>
            <span class="min-w-0">
                <span class="block font-black text-base sm:text-xl truncate" x-text="i('institution_name')"></span>
                <span class="block text-[10px] sm:text-xs font-bold text-slate-500 dark:text-slate-400" x-text="i('established')"></span>
            </span>
        </a>
        <div class="flex items-center gap-2">
            <a href="index.html" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-full font-bold hover:bg-slate-100 dark:hover:bg-slate-800"><i class="fa-solid fa-house"></i><span x-text="i('home')"></span></a>
            <button @click="toggleTheme()" class="w-10 h-10 rounded-full founder-header-control flex items-center justify-center"><i class="fa-solid" :class="darkMode?'fa-sun':'fa-moon'"></i></button>
            <button @click="switchLang()" class="px-3 py-2 rounded-full founder-header-control font-black text-xs" x-text="lang==='bn'?'EN':'বাংলা'"></button>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-8 sm:py-12">
    <section class="relative overflow-hidden rounded-[2rem] sm:rounded-[3rem] bg-slate-900 text-white p-6 sm:p-10 lg:p-14 hero-glow shadow-2xl">
        <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent"></div>
        <div class="relative grid lg:grid-cols-[320px_1fr] gap-8 lg:gap-12 items-center">
            <div class="mx-auto w-56 h-56 sm:w-72 sm:h-72 rounded-[2rem] overflow-hidden bg-white/10 border border-white/15 p-2 shadow-2xl">
                <img src="assets/images/Sirajul-Islam-Founder.jpg" alt="মরহুম আলহাজ্ব সিরাজুল ইসলাম" class="w-full h-full object-cover rounded-[1.5rem]">
            </div>
            <div>
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-gold text-slate-950 font-black text-xs uppercase tracking-wider"><i class="fa-solid fa-star"></i><span x-text="i('founder_label')"></span></span>
                <h1 class="text-3xl sm:text-5xl font-black leading-tight mt-5" x-text="i('founder_name')"></h1>
                <p class="mt-4 text-slate-300 text-base sm:text-lg leading-relaxed max-w-3xl" x-text="i('intro')"></p>
            </div>
        </div>
    </section>

    <section class="grid lg:grid-cols-3 gap-6 mt-8">
        <div class="lg:col-span-2 glass rounded-3xl p-6 sm:p-8 shadow-xl">
            <h2 class="text-2xl sm:text-3xl font-black mb-5 flex items-center gap-3"><span class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-300 flex items-center justify-center"><i class="fa-solid fa-user"></i></span><span x-text="i('profile_title')"></span></h2>
            <p class="text-slate-600 dark:text-slate-300 leading-8" x-text="i('profile')"></p>
        </div>
        <div class="glass rounded-3xl p-6 sm:p-8 shadow-xl">
            <h2 class="text-xl sm:text-2xl font-black mb-5" x-text="i('personal_title')"></h2>
            <dl class="space-y-4 text-sm">
                <template x-for="item in personal" :key="item.k">
                    <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
                        <dt class="font-bold text-slate-500 dark:text-slate-400" x-text="i(item.k)"></dt>
                        <dd class="font-black mt-1" x-text="i(item.v)"></dd>
                    </div>
                </template>
            </dl>
        </div>
    </section>

    <section class="glass rounded-3xl p-6 sm:p-8 shadow-xl mt-6">
        <h2 class="text-2xl sm:text-3xl font-black mb-6 flex items-center gap-3"><span class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-300 flex items-center justify-center"><i class="fa-solid fa-graduation-cap"></i></span><span x-text="i('education_title')"></span></h2>
        <div class="grid md:grid-cols-2 gap-4">
            <template x-for="item in education" :key="item.year">
                <article class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/60 p-5">
                    <div class="text-brand-gold font-black text-lg" x-text="item.year"></div>
                    <h3 class="font-black mt-1" x-text="i(item.title)"></h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-2" x-text="i(item.detail)"></p>
                </article>
            </template>
        </div>
    </section>

    <section class="glass rounded-3xl p-6 sm:p-8 shadow-xl mt-6">
        <h2 class="text-2xl sm:text-3xl font-black mb-6 flex items-center gap-3"><span class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-300 flex items-center justify-center"><i class="fa-solid fa-building"></i></span><span x-text="i('institutions_title')"></span></h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <template x-for="(name,index) in institutions" :key="index">
                <div class="flex items-center gap-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/60 dark:bg-slate-900/50 p-4">
                    <span class="w-9 h-9 rounded-xl bg-slate-900 dark:bg-brand-gold text-white dark:text-slate-950 flex items-center justify-center font-black text-sm" x-text="index+1"></span>
                    <span class="font-bold text-sm" x-text="i(name)"></span>
                </div>
            </template>
        </div>
    </section>

    <section class="grid lg:grid-cols-2 gap-6 mt-6">
        <div class="glass rounded-3xl p-6 sm:p-8 shadow-xl">
            <h2 class="text-2xl font-black mb-4"><i class="fa-solid fa-hand-holding-heart text-rose-500 mr-2"></i><span x-text="i('service_title')"></span></h2>
            <p class="text-slate-600 dark:text-slate-300 leading-8" x-text="i('service')"></p>
        </div>
        <div class="glass rounded-3xl p-6 sm:p-8 shadow-xl">
            <h2 class="text-2xl font-black mb-4"><i class="fa-solid fa-people-roof text-indigo-500 mr-2"></i><span x-text="i('family_title')"></span></h2>
            <p class="text-slate-600 dark:text-slate-300 leading-8" x-text="i('family')"></p>
        </div>
    </section>

    <div class="mt-8 text-center">
        <a href="index.html" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-black shadow-lg hover:-translate-y-0.5 transition" x-text="i('back_home')"></a>
    </div>
</main>

<footer class="mt-12 bg-slate-950 text-slate-300">
    <div class="max-w-7xl mx-auto px-4 py-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <div>
            <div class="flex items-center gap-3"><img src="assets/images/ASPI-Logo.png" class="w-12 h-12 rounded-xl bg-white p-1 object-contain" alt="ASPI"><div><div class="font-black text-white" x-text="i('institution_name')"></div><div class="text-xs text-slate-500" x-text="i('established')"></div></div></div>
            <p class="mt-4 text-sm leading-7 text-slate-400" x-text="i('footer_desc')"></p>
        </div>
        <div>
            <h3 class="font-black text-white mb-4" x-text="i('quick_links')"></h3>
            <a href="index.html#about" class="block text-sm hover:text-brand-gold mb-2" x-text="i('about_us')"></a>
            <a href="index.html#admission" class="block text-sm hover:text-brand-gold mb-2" x-text="i('admission')"></a>
            <a href="founder.php" class="block text-sm hover:text-brand-gold" x-text="i('founder_profile')"></a>
        </div>
        <div>
            <h3 class="font-black text-white mb-4" x-text="i('contact')"></h3>
            <p class="text-sm text-slate-400 leading-7" x-text="i('institution_address')"></p>
            <p class="text-sm text-slate-400 mt-2" x-text="i('institution_phone')"></p>
            <p class="text-sm text-slate-400 mt-2" x-text="i('institution_email')"></p>
        </div>
    </div>
    <div class="border-t border-slate-800 text-center py-5 text-xs text-slate-500" x-text="i('copyright')"></div>
</footer>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('founderApp', () => ({
        lang:'bn', darkMode:false,
        tr:{
            bn:{
                institution_name:'আসহাব সিরাজ পলিটেকনিক ইনস্টিটিউট', established:'প্রতিষ্ঠিত: ২০১৯ | পাঠদান অনুমোদন: ২০২১',
                home:'হোম', founder_profile:'প্রতিষ্ঠাতা', founder_label:'প্রতিষ্ঠাতা মহোদয়',
                founder_name:'মরহুম আলহাজ্ব সিরাজুল ইসলাম', intro:'মরহুম আলহাজ্ব সিরাজুল ইসলাম ছিলেন একজন নীতিবান, দূরদর্শী ও মানবিক ব্যক্তিত্ব। সৎ জীবনযাপন, সমাজসেবা ও জনকল্যাণমূলক কর্মকাণ্ডের মাধ্যমে তিনি মানুষের হৃদয়ে স্থায়ী স্থান করে নিয়েছেন।',
                profile_title:'পরিচিতি', profile:'শিক্ষাজীবন শেষে তিনি কর্মজীবনে প্রবেশ করেন এবং দায়িত্বশীলতা, সততা ও নিষ্ঠার সঙ্গে বিভিন্ন খাতে গুরুত্বপূর্ণ অবদান রাখেন। একজন সফল উদ্যোক্তা হিসেবে তিনি বহু প্রতিষ্ঠান প্রতিষ্ঠা করেন, যেগুলো আজও তাঁর নীতিবোধ ও দূরদর্শিতার প্রতিফলন বহন করছে। ধর্মীয় ও মানবিক কাজেও তিনি ছিলেন নিরলস কর্মী।',
                personal_title:'ব্যক্তিগত তথ্য', birth_label:'জন্ম', birth:'৩০ জানুয়ারি ১৯৫৮', death_label:'মৃত্যু', death:'২৭ এপ্রিল ২০২১', father_label:'পিতা', father:'মরহুম আলহাজ্ব আসহাব মিয়া', mother_label:'মাতা', mother:'আলহাজ্ব মেহেরুন্নেসা কুসুমবালা', birthplace_label:'জন্মস্থান', birthplace:'জামিরজুরী, দোহাজারী পৌরসভা, চট্টগ্রাম',
                education_title:'শিক্ষাজীবন', edu1:'দোহাজারী জামিরজুরী আ. রহমান উচ্চ বিদ্যালয়', edu1d:'মানবিক বিভাগে দ্বিতীয় স্থান', edu2:'গাছবাড়ীয়া মহাবিদ্যালয়', edu2d:'মানবিক বিভাগে তৃতীয় স্থান', edu3:'চট্টগ্রাম বিশ্ববিদ্যালয় — বিএ', edu3d:'ইসলামের ইতিহাস; দ্বিতীয় বিভাগ', edu4:'চট্টগ্রাম বিশ্ববিদ্যালয় — এমএ', edu4d:'ইসলামের ইতিহাস ও সংস্কৃতি; দ্বিতীয় বিভাগ',
                institutions_title:'প্রতিষ্ঠানসমূহ', i1:'টোটাল শিপিং',i2:'সাইগন এন্টারপ্রাইজ',i3:'আফনান এন্টারপ্রাইজ',i4:'ওরিয়েন্টাল এজেন্টস লিঃ',i5:'সালুটিকর এসোসিয়েটস',i6:'রাজু এন্টারপ্রাইজ',i7:'টোটাল ফিসিং',i8:'আমির এন্ড সন্স লিঃ',i9:'বিটু রেস্টুরেন্ট',i10:'বাগান এন্টারপ্রাইজ',i11:'সেইফ এন্ড সাউন্ড শিপিং',i12:'সেইফ এন্ড সাউন্ড ইন্সপেকশন',i13:'আসহাব সিরাজ ফাউন্ডেশন',i14:'আসহাব সিরাজ পলিটেকনিক ইনস্টিটিউট',
                service_title:'সামাজিক ও মানবিক অবদান', service:'মসজিদ, মাদ্রাসা, এতিমখানা এবং অন্যান্য জনকল্যাণমূলক প্রতিষ্ঠানের উন্নয়নে তাঁর অবদান আজও শ্রদ্ধার সঙ্গে স্মরণ করা হয়।',
                family_title:'পরিবার', family:'তিনি ছিলেন এক সুখী ও সম্মানিত পরিবারের কর্তা। স্ত্রী বেগম রাজিয়া সোলতানা এবং সন্তান জনাব আফনান ইললাম, জনাবা সাবরিনা সুরভি আসফি ও নাফিস হাসান ইসলাম তাঁর আদর্শ ও মূল্যবোধ অনুসরণ করে এগিয়ে চলেছেন।',
                footer_desc:'কারিগরি শিক্ষার মাধ্যমে শিক্ষিত জাতি প্রকৃত উন্নয়নের পথে এগিয়ে যেতে পারে। আমরা আধুনিক প্রযুক্তিনির্ভর শিক্ষার প্রতি প্রতিশ্রুতিবদ্ধ।', quick_links:'দ্রুত লিংক', about_us:'আমাদের সম্পর্কে', admission:'ভর্তি', contact:'যোগাযোগ', institution_address:'দক্ষিণ হাশিমপুর (জামিরজুরী রাস্তার মাথা), দোহাজারী, চন্দনাইশ, চট্টগ্রাম', institution_phone:'+৮৮০ ১৮৪৭-৩১০৩১০', institution_email:'ctgaspi@gmail.com', copyright:'© ২০২৬ আসহাব সিরাজ পলিটেকনিক ইনস্টিটিউট। সর্বস্বত্ব সংরক্ষিত।', back_home:'মূল ওয়েবসাইটে ফিরে যান'
            },
            en:{
                institution_name:'Ashab Siraj Polytechnic Institute', established:'Established: 2019 | Teaching Approval: 2021',
                home:'Home', founder_profile:'Founder', founder_label:'Founder',
                founder_name:'Late Alhaj Sirajul Islam', intro:'Late Alhaj Sirajul Islam was a principled, visionary and compassionate personality who earned a lasting place in people’s hearts through an honest life, social service and public-welfare activities.',
                profile_title:'Profile', profile:'After completing his education, he entered professional life and made important contributions across different sectors with responsibility, honesty and dedication. As a successful entrepreneur, he established many organizations that continue to reflect his principles and foresight. He was also deeply committed to religious and humanitarian work.',
                personal_title:'Personal Information', birth_label:'Birth', birth:'30 January 1958', death_label:'Death', death:'27 April 2021', father_label:'Father', father:'Late Alhaj Ashab Mia', mother_label:'Mother', mother:'Alhaj Meherunnesa Kusumbala', birthplace_label:'Birthplace', birthplace:'Jamirjuri, Dohazari Municipality, Chattogram',
                education_title:'Education', edu1:'Dohazari Jamirjuri A. Rahman High School', edu1d:'Second position, Humanities', edu2:'Gachbaria College', edu2d:'Third position, Humanities', edu3:'University of Chittagong — BA', edu3d:'Islamic History; Second Division', edu4:'University of Chittagong — MA', edu4d:'Islamic History & Culture; Second Division',
                institutions_title:'Organizations & Enterprises', i1:'Total Shipping',i2:'Saigon Enterprise',i3:'Afnan Enterprise',i4:'Oriental Agents Ltd.',i5:'Salutikor Associates',i6:'Raju Enterprise',i7:'Total Fishing',i8:'Amir & Sons Ltd.',i9:'Bitu Restaurant',i10:'Bagan Enterprise',i11:'Safe & Sound Shipping',i12:'Safe & Sound Inspection',i13:'Ashab Siraj Foundation',i14:'Ashab Siraj Polytechnic Institute',
                service_title:'Social & Humanitarian Contribution', service:'His contribution to the development of mosques, madrasas, orphanages and other public-welfare institutions is remembered with respect to this day.',
                family_title:'Family', family:'He was the head of a happy and respected family. His wife Begum Raziya Sultana and children Afnan Illam, Sabrina Survi Asfi and Nafis Hasan Islam continue to move forward in different fields while upholding his ideals and values.',
                footer_desc:'A nation educated through technical education can achieve true development. We are committed to modern, technology-based education.', quick_links:'Quick Links', about_us:'About Us', admission:'Admission', contact:'Contact', institution_address:'South Hashimpur (Jamirjuri Road Head), Dohazari, Chandanaish, Chattogram', institution_phone:'+880 1847-310310', institution_email:'ctgaspi@gmail.com', copyright:'© 2026 Ashab Siraj Polytechnic Institute. All rights reserved.', back_home:'Back to Main Website'
            }
        },
        personal:[{k:'birth_label',v:'birth'},{k:'death_label',v:'death'},{k:'father_label',v:'father'},{k:'mother_label',v:'mother'},{k:'birthplace_label',v:'birthplace'}],
        education:[{year:'1973',title:'edu1',detail:'edu1d'},{year:'1975',title:'edu2',detail:'edu2d'},{year:'1978',title:'edu3',detail:'edu3d'},{year:'',title:'edu4',detail:'edu4d'}],
        institutions:['i1','i2','i3','i4','i5','i6','i7','i8','i9','i10','i11','i12','i13','i14'],
        i(k){return this.tr[this.lang][k] ?? k;},
        init(){
            const saved=localStorage.getItem('aspi_lang');
            this.lang=['bn','en'].includes(saved)?saved:(navigator.language?.toLowerCase().startsWith('en')?'en':'bn');
            const savedTheme=localStorage.getItem('aspi_theme');
            this.darkMode=savedTheme==='dark'||(!savedTheme&&window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark',this.darkMode);
            document.documentElement.lang=this.lang; document.title=this.i('founder_profile')+' | ASPI';
        },
        toggleTheme(){this.darkMode=!this.darkMode;document.documentElement.classList.toggle('dark',this.darkMode);localStorage.setItem('aspi_theme',this.darkMode?'dark':'light');},
        switchLang(){this.lang=this.lang==='bn'?'en':'bn';localStorage.setItem('aspi_lang',this.lang);document.documentElement.lang=this.lang; document.title=this.i('founder_profile')+' | ASPI';}
    }));
});
</script>
</body>
</html>