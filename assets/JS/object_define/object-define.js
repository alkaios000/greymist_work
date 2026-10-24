document.addEventListener("DOMContentLoaded", () => {
    // === DRAG SOURCE ===
    document.querySelectorAll(".gm-object-dropable").forEach(t => {
        // lewati elemen yang bisa diedit
        if (t.isContentEditable || t.closest('[contenteditable="true"]')) return;

        t.setAttribute("draggable", "true");
        t.addEventListener("dragstart", e => {
            // kalau klik berasal dari text yang bisa diedit, jangan mulai drag
            if (e.target.isContentEditable || e.target.closest('[contenteditable="true"]')) {
                e.preventDefault();
                return;
            }
            e.dataTransfer.setData("text/plain", t.dataset.type || "");
            e.dataTransfer.effectAllowed = "copy";
        });
    });

    // === DRAG SOURCE (TEMPLATES - DELEGATION) ===
    document.addEventListener("dragstart", e => {
        // Jangan drag jika yang diklik adalah tombol delete
        if (e.target.closest(".gm-comp-card-del-btn")) {
            e.preventDefault();
            return;
        }

        const t = e.target.closest(".gm-template-dropable");
        if (t) {
            e.dataTransfer.setData("text/plain", t.dataset.type || "template");
            const templateTag = t.querySelector("template");
            let htmlToDrop = templateTag ? templateTag.innerHTML : "";
            if (!htmlToDrop && templateTag && templateTag.content) {
                const tempDiv = document.createElement("div");
                tempDiv.appendChild(templateTag.content.cloneNode(true));
                htmlToDrop = tempDiv.innerHTML;
            }
            if (!htmlToDrop && t.dataset.htmlContent) {
                try { htmlToDrop = decodeURIComponent(t.dataset.htmlContent); } catch(err){}
            }

            if (htmlToDrop) {
                localStorage.setItem("gm_drag_template", htmlToDrop);
                try { e.dataTransfer.setData("template-html", htmlToDrop); } catch(err){}
            }
            e.dataTransfer.effectAllowed = "copy";
        }
    });


    // === BUAT ELEMEN SESUAI DATASET TYPE ===
    function createElementByType(type, iconClass = null) {
        let el;
        // Tangani icon khusus
        if (type === "icon" && iconClass) {
            el = document.createElement("div");
            el.classList.add("gm-object");
            const iElem = document.createElement("i");
            iElem.className = iconClass;
            iElem.style.fontSize = "2em";
            el.appendChild(iElem);
        } else {
            switch (type) {

                // BLOCK ELEMENT
                // -----------------------------------------------------------------------------------------------------------------------------------------------------

                // body (tempat utama untuk html structure)--------------------------------------------------------------------------------------------------------------
                case "body":
                    // main attr
                    el = document.createElement("body");
                    // style
                    el.style.width = "100%";
                    el.style.height = "100%";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    // el.style.border = "1px solid #bbb";

                    break;

                // div (division, elemen pembungkus, elemen layouting)--------------------------------------------------------------------------------------------------------------
                case "div":
                    // main attr
                    el = document.createElement("div");
                    // style
                    el.style.width = "90px";
                    el.style.height = "50px";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    el.style.display = "flex";
                    el.style.background = 'lightgrey';
                    // el.style.border = "1px solid #bbb";

                    break;

                // header (semantic tag membuat head web)--------------------------------------------------------------------------------------------------------------
                case "header":
                    // main attr
                    el = document.createElement("header");
                    // style
                    el.style.width = "100%";
                    el.style.height = "75px";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    el.style.display = "flex";
                    el.style.border = "1px solid #bbb";

                    break;

                // nav (navigation, semantic tag untuk membuat navigasi menu)--------------------------------------------------------------------------------------------------------------
                case "nav":
                    // main attr
                    el = document.createElement("nav");
                    // style
                    el.style.width = "90px";
                    el.style.height = "50px";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    el.style.display = "flex";
                    el.style.border = "1px solid #bbb";

                    break;

                // main (semantic tag untuk menampung konten utama)--------------------------------------------------------------------------------------------------------------
                case "main":
                    // main attr
                    el = document.createElement("main");
                    // style
                    el.style.width = "100%";
                    el.style.height = "400px";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    el.style.display = "flex";
                    el.style.border = "1px solid #bbb";

                    break;

                // section (semantic tag untuk mendefinisikan section/ pengelompokan tampilan/ tab/ menu)--------------------------------------------------------------------------------------------------------------
                case "section":
                    // main attr
                    el = document.createElement("section");
                    // style
                    el.style.width = "100%";
                    el.style.height = "150px";
                    el.style.margin = "0";
                    el.style.padding = "0.5em";
                    el.style.display = "flex";
                    el.style.border = "1px solid #bbb";

                    break;

                // article (semantik tag untuk menaruh konten article yang biasanya ber tema berbeda dari konten utama)--------------------------------------------------------------------------------------------------------------
                case "article":
                    // main attr
                    el = document.createElement("article");
                    // style
                    el.style.width = "100%";
                    el.style.height = "100px";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    el.style.display = "flex";
                    el.style.border = "1px solid #bbb";

                    break;

                // aside (semantic tag untuk membuat sidebar)--------------------------------------------------------------------------------------------------------------
                case "aside":
                    // main attr
                    el = document.createElement("aside");
                    // style
                    el.style.width = "250px";
                    el.style.height = "100%";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    el.style.display = "flex";
                    el.style.border = "1px solid #bbb";

                    break;

                // footer (semantic tag untuk membuat footer cont)--------------------------------------------------------------------------------------------------------------
                case "footer":
                    // main attr
                    el = document.createElement("footer");
                    // style
                    el.style.width = "100%";
                    el.style.height = "50px";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    el.style.display = "flex";
                    el.style.border = "1px solid #bbb";

                    break;

                // form (formulir container, khusus untuk dimasuki input tag & button)--------------------------------------------------------------------------------------------------------------
                case "form":
                    // main attr
                    el = document.createElement("form");
                    el.method = "post";
                    el.action = "";
                    el.autocomplete = "off";
                    // style
                    el.style.width = "200px";
                    el.style.height = "250px";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    el.style.borderRadius = "5px";
                    el.style.display = "flex";
                    el.style.border = "1px solid #bbb";

                    break;

                // fieldset (semantic tag biasanya masuk didalam form, bisa juga di elemen lain, fungsi nya untuk memberikan pembungkus khusus dengan tema khusus (legend))--------------------------------------------------------------------------------------------------------------
                case "fieldset":
                    // main attr
                    el = document.createElement("fieldset");
                    // style
                    el.style.width = "100%";
                    el.style.height = "50%";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    el.style.display = "flex";
                    el.style.border = "1px solid #bbb";

                    break;

                // legend (memberikan judul dengan layout khusus pada fieldset, hanya berfungsi di dalam fieldset)--------------------------------------------------------------------------------------------------------------
                case "legend":
                    el = document.createElement("legend");
                    el.textContent = "legend";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    el.style.display = "flex";
                    el.style.border = "1px solid #bbb";
                    el.contentEditable = "true";

                    break;

                // table (mendefinisikan table & child)--------------------------------------------------------------------------------------------------------------
                case "table":
                    // main attr
                    el = document.createElement("table");
                    el.classList.add("gm-object");
                    // style
                    el.style.width = "250px";
                    el.style.height = "150px";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    el.style.border = "1px solid #bbb";
                    el.style.borderCollapse = "collapse";
                    el.style.borderRadius = "5px";

                    // caption (judul table)
                    // main attr
                    let caption = document.createElement("caption");
                    caption.classList.add("gm-object");
                    caption.textContent = "Judul Tabel";
                    caption.contentEditable = "true";
                    // style
                    caption.style.captionSide = "top";
                    caption.style.fontWeight = "bold";
                    caption.style.padding = "4px";

                    // ===== Struktur dasar =====
                    // pendefinisian header table
                    let thead = document.createElement("thead");
                    thead.classList.add("gm-object");

                    // pendefinisian tubuh table
                    let tbody = document.createElement("tbody");
                    tbody.classList.add("gm-object");

                    // pemberian baris pada
                    let trHead = document.createElement("tr");
                    trHead.classList.add("gm-object");

                    // pendefinisian 3 kolom table head default
                    ["Kolom 1", "Kolom 2", "Kolom 3"].forEach(text => {
                        const th = document.createElement("th");
                        th.classList.add("gm-object");
                        th.contentEditable = "true";
                        th.textContent = text;
                        th.style.border = "1px solid #ccc";
                        th.style.padding = "4px";
                        // memasukkan th kedalam tr
                        trHead.appendChild(th);
                    });
                    // memasukkan tr kedalam thead
                    thead.appendChild(trHead);

                    // pendefinisian tr untuk body
                    let trBody = document.createElement("tr");
                    trBody.classList.add("gm-object");

                    // pendefinisan 3 data untuk mengisi table data
                    ["Data 1", "Data 2", "Data 3"].forEach(text => {
                        const td = document.createElement("td");
                        td.classList.add("gm-object");
                        td.textContent = text;
                        td.style.border = "1px solid #ccc";
                        td.style.padding = "4px";
                        td.contentEditable = "true";
                        // memasukkan td kedalam tr body
                        trBody.appendChild(td);
                    });
                    // memasukkan tr kedalam table body
                    tbody.appendChild(trBody);
                    // memasukan caption, header table, dan body table kedalam element table
                    el.append(caption, thead, tbody);

                    break;

                // unordered list (daftar tidak berurutan, digunakan untuk list yang tidak berisi logik yang harus berurutan)--------------------------------------------------------------------------------------------------------------
                case "ul":
                    // main attr
                    el = document.createElement("ul");
                    el.classList.add("gm-object");
                    // style
                    el.style.width = "150px";
                    el.style.minHeight = "60px";
                    el.style.margin = "0";
                    el.style.padding = "8px 24px";
                    el.style.border = "1px solid #bbb";
                    el.style.borderRadius = "5px";
                    el.style.listStyleType = "square";
                    el.style.listStylePosition = "outside";
                    // ===== List item dasar =====
                    ["Item 1", "Item 2", "Item 3"].forEach(text => {
                        let li = document.createElement("li");
                        li.classList.add("gm-object");
                        li.textContent = text;
                        el.appendChild(li);
                        li.contentEditable = "true";
                        li.style.padding = "2px 0";
                    });

                    break;

                // ordered list (list berurutan, digunakan untuk list yang berisi logik berurut, langkah-langkah (1,2,3/a,b,c))--------------------------------------------------------------------------------------------------------------
                case "ol":
                    // main attr
                    el = document.createElement("ol");
                    el.classList.add("gm-object");
                    // style
                    el.style.width = "150px";
                    el.style.minHeight = "60px";
                    el.style.margin = "0";
                    el.style.padding = "8px 24px"; // beri ruang kiri agar bullet tampil
                    el.style.border = "1px solid #bbb";
                    el.style.borderRadius = "5px";
                    el.style.listStyleType = "square";
                    el.style.listStylePosition = "outside"; // pastikan di luar padding

                    // ===== List item dasar =====
                    ["Item 1", "Item 2", "Item 3"].forEach(text => {
                        let li = document.createElement("li");
                        li.classList.add("gm-object");
                        li.textContent = text;
                        li.style.padding = "2px 0";
                        el.appendChild(li);
                        li.contentEditable = "true";
                    });

                    break;

                // definition list (container berisi istilah istilah dan definisinya)--------------------------------------------------------------------------------------------------------------
                case "dl":
                    // main attr
                    el = document.createElement("dl");
                    el.classList.add("gm-object");
                    // style
                    el.style.width = "200px";
                    el.style.minHeight = "80px";
                    el.style.margin = "0";
                    el.style.padding = "8px 12px";
                    el.style.border = "1px solid #bbb";
                    el.style.borderRadius = "5px";
                    // isi default
                    // definition term
                    let dt1 = document.createElement("dt");
                    dt1.classList.add("gm-object");
                    dt1.textContent = "HTML";
                    dt1.style.fontWeight = "bold";
                    dt1.contentEditable = "true"
                    // definition description
                    let dd1 = document.createElement("dd");
                    dd1.classList.add("gm-object");
                    dd1.textContent = "Bahasa markup untuk membuat struktur halaman web.";
                    dd1.style.marginLeft = "16px";
                    dd1.contentEditable = "true"
                    // definition term
                    let dt2 = document.createElement("dt");
                    dt2.classList.add("gm-object");
                    dt2.textContent = "CSS";
                    dt2.style.fontWeight = "bold";
                    dt2.contentEditable = "true"
                    // definition description
                    let dd2 = document.createElement("dd");
                    dd2.classList.add("gm-object");
                    dd2.textContent = "Bahasa yang digunakan untuk mendesain tampilan web.";
                    dd2.style.marginLeft = "16px";
                    dd2.contentEditable = "true"
                    // memasukkan term dan description bawaan kedalam element datalist
                    el.append(dt1, dd1, dt2, dd2);

                    break;

                // definition term (istilah)-------------------------------------------------------------------------------------------------------------------------
                case "dt":
                    // main attr
                    el = document.createElement("dt");
                    el.classList.add("gm-object");
                    el.textContent = "Istilah";
                    el.contentEditable = "true"
                    // style
                    el.style.padding = "2px 0";

                    break;

                // definition description (definisi dari istilah)--------------------------------------------------------------------------------------------------------------
                case "dd":
                    // main attr
                    el = document.createElement("dd");
                    el.textContent = "Penjelasan dari istilah di atas.";
                    el.contentEditable = "true"
                    // style
                    el.classList.add("gm-object");
                    el.style.marginLeft = "16px";
                    el.style.padding = "2px 0";
                    break;

                // figure (membungkus sebuah foto atau citra dengan figcaption)--------------------------------------------------------------------------------------------------------------
                case "figure":
                    // main attr
                    el = document.createElement("figure");
                    el.classList.add("gm-object");
                    // style
                    el.style.width = "200px";
                    el.style.minHeight = "60px";
                    el.style.margin = "0";
                    el.style.padding = "8px";
                    el.style.border = "1px solid #bbb";
                    el.style.borderRadius = "5px";
                    el.style.display = "inline-block";
                    el.style.textAlign = "center";
                    el.style.backgroundColor = "#fafafa";

                    break;

                // figcaption (memberikan caption khusus kepada tag figure dan citra di dalamnya)--------------------------------------------------------------------------------------------------------------
                case "figcaption":
                    // main attr
                    el = document.createElement("figcaption");
                    el.classList.add("gm-object");
                    el.textContent = "Keterangan gambar/ilustrasi";
                    el.contentEditable = "true";
                    // style
                    el.style.fontSize = "13px";
                    el.style.color = "#333";
                    el.style.marginTop = "4px";

                    break;

                // detail (container utama yang bisa dibuka tutup ketika di klick)--------------------------------------------------------------------------------------------------------------
                case "details":
                    // main attr
                    el = document.createElement("details");
                    el.classList.add("gm-object");
                    // style
                    el.style.width = "250px";
                    el.style.minHeight = "40px";
                    el.style.margin = "0";
                    el.style.padding = "6px 8px";
                    el.style.border = "1px solid #bbb";
                    el.style.borderRadius = "5px";
                    el.style.backgroundColor = "#fafafa";
                    // summary define(child) (text judul dari details)
                    let sum = document.createElement("summary");
                    sum.classList.add("gm-object");
                    sum.textContent = "Klik untuk lihat detail";
                    sum.style.cursor = "pointer";
                    sum.style.fontWeight = "bold";
                    sum.style.padding = "2px 0";
                    // paragraph(child)
                    let p = document.createElement("p");
                    p.classList.add("gm-object");
                    p.textContent = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla facilisi. Sed euismod, sapien sit amet ultricies.";
                    p.contentEditable = "true"
                    p.style.margin = "6px 0 0 10px";
                    p.style.fontSize = "13px";
                    // memasukkan sum dan p kemudian didalam details
                    el.appendChild(sum);
                    el.appendChild(p);

                    break;

                // summary (hanya berfungsi didalam details, berfungsi sebagai judul dari details yang mewakili isi dari details)--------------------------------------------------------------------------------------------------------------
                case "summary":
                    // main attr
                    el = document.createElement("summary");
                    el.classList.add("gm-object");
                    el.textContent = "Judul ringkas";
                    el.contentEditable = "true"
                    // style
                    el.style.cursor = "pointer";
                    el.style.fontWeight = "bold";
                    el.style.padding = "2px 0";

                    break;

                // dialog (membuat automatic modal open and close)--------------------------------------------------------------------------------------------------------------
                case "dialog":
                    // main attr
                    el = document.createElement("dialog");
                    el.classList.add("gm-object");
                    // style
                    el.style.width = "300px";
                    el.style.minHeight = "120px";
                    el.style.padding = "10px";
                    el.style.border = "1px solid #bbb";
                    el.style.borderRadius = "8px";
                    el.style.backgroundColor = "#fff";
                    el.style.boxShadow = "0 4px 10px rgba(0,0,0,0.2)";
                    // default child
                    el.innerHTML = `
        <button class="gm-object" onclick="this.closest('dialog').close()">Tutup</button>`;
                    // default tampil
                    setTimeout(() => el.show(), 0);

                    break;

                // canvas (sebagai field menggambar grafik secara dinamis menggunakan JavaScript)--------------------------------------------------------------------------------------------------------------
                case "canvas":
                    // main attr
                    el = document.createElement("canvas");
                    el.classList.add("gm-object");
                    el.width = 300;
                    el.height = 150;
                    // style
                    el.style.border = "1px solid #bbb";
                    el.style.borderRadius = "5px";
                    el.style.backgroundColor = "#fff";

                    break;

                // iframe (untuk menyematkan (embed) halaman web lain di dalam halaman web kita sendiri)--------------------------------------------------------------------------------------------------------------
                case "iframe":
                    // main attr
                    el = document.createElement("iframe");
                    el.classList.add("gm-object");
                    el.allowFullscreen = true;
                    el.loading = "lazy";
                    // tampilkan contoh situs default
                    el.src = "https://example.com";
                    // style
                    el.style.width = "300px";
                    el.style.height = "200px";
                    el.style.border = "1px solid #bbb";
                    el.style.borderRadius = "5px";
                    el.style.backgroundColor = "#fff";

                    break;

                // hr (horizontal rule) (berfungsi sebagai pemisah antar bagian konten)--------------------------------------------------------------------------------------------------------------
                case "hr":
                    // main attr
                    el = document.createElement("hr");
                    el.classList.add("gm-object");
                    // style
                    el.style.width = "100%";
                    el.style.border = "none";
                    el.style.borderTop = "0.8px solid #bbb";
                    el.style.margin = "8px 0";

                    break;

                // blockquote(menampilkan kutipan panjang (block quote) dari suatu sumber)--------------------------------------------------------------------------------------------------------------
                case "blockquote":
                    // main attr
                    el = document.createElement("blockquote");
                    el.classList.add("gm-object");
                    el.textContent = "Ini adalah contoh kutipan panjang dari suatu sumber.";
                    el.contentEditable = "true";
                    // style
                    el.style.width = "250px";
                    el.style.margin = "10px";
                    el.style.padding = "8px 12px";
                    el.style.borderLeft = "4px solid #bbb";
                    el.style.backgroundColor = "#f9f9f9";
                    el.style.fontStyle = "italic";

                    break;

                // address (menampilkan informasi kontak atau identitas penulis/pemilik halaman web)--------------------------------------------------------------------------------------------------------------
                case "address":
                    // main attr
                    el = document.createElement("address");
                    el.classList.add("gm-object");
                    // style
                    el.style.width = "250px";
                    el.style.margin = "8px";
                    el.style.padding = "8px 10px";
                    el.style.fontStyle = "italic";
                    el.style.backgroundColor = "#f8f8f8";
                    el.style.border = "1px solid #ddd";
                    el.style.borderRadius = "5px";

                    // child 1: paragraf pertama
                    let p1 = document.createElement("p");
                    p1.classList.add("gm-object");
                    p1.textContent = "Ditulis oleh:";
                    p1.contentEditable = "true";

                    // child 2: paragraf kedua (link)
                    let p2 = document.createElement("a");
                    p2.classList.add("gm-object");
                    p2.textContent = "email@contoh.com";
                    p2.contentEditable = "true";

                    // child 3: paragraf ketiga (alamat)
                    let p3 = document.createElement("p");
                    p3.classList.add("gm-object");
                    p3.textContent = "Jl. Mawar No. 123, Jakarta";
                    p3.contentEditable = "true";

                    el.append(p1, p2, p3);

                    break;

                // INPUT ELEMENT
                // ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

                // input-text (input khusus text)--------------------------------------------------------------------------------------------------------------
                case "input-text":
                    // main attr
                    el = document.createElement("input");
                    el.type = "text";
                    el.classList.add("gm-object");
                    el.placeholder = "Input text";
                    // style
                    el.style.padding = "8px 12px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "200px";
                    el.style.height = "50px";
                    el.style.margin = "0";

                    break;

                // input-password (input untuk memasukkan password, hampir sama seperti text tapi akan dilakukan sensor pada character yang di ketik)--------------------------------------------------------------------------------------------------------------
                case "input-password":
                    // main attr
                    el = document.createElement("input");
                    el.type = "password";
                    el.classList.add("gm-object");
                    el.placeholder = "Input password";
                    // Style
                    el.style.padding = "8px 12px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "200px";
                    el.style.margin = "0";

                    break;

                // input-email (input yang memastikan char yang dimasukkan memiliki format mail address)--------------------------------------------------------------------------------------------------------------
                case "input-email":
                    // main attr
                    el = document.createElement("input");
                    el.type = "email";
                    el.classList.add("gm-object");
                    el.placeholder = "Input email";
                    // Style
                    el.style.padding = "8px 12px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "200px";
                    el.style.margin = "0";

                    break;

                // input-number (hanya menerima format nomor)--------------------------------------------------------------------------------------------------------------
                case "input-number":
                    el = document.createElement("input");
                    el.type = "number";
                    el.classList.add("gm-object");
                    el.placeholder = "Input number";
                    // Style
                    el.style.padding = "8px 12px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "200px";
                    el.style.margin = "0";

                    break;

                // input-tel (menerima format angka, dan memastikan input hanya sampai batas yang diberikan developer)--------------------------------------------------------------------------------------------------------------
                case "input-tel":
                    el = document.createElement("input");
                    el.type = "tel";
                    el.classList.add("gm-object");
                    el.placeholder = "0812-3456-7890";
                    // Optional: validasi pattern (hanya angka, 10-13 digit)
                    el.pattern = "[0-9]{10,13}";
                    el.required = true;
                    // Style
                    el.style.padding = "8px 12px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "200px";
                    el.style.margin = "0";
                    el.style.boxSizing = "border-box";

                    break;

                // input-url (memastikan yang diinput merupakan sebuah tautan/link)--------------------------------------------------------------------------------------------------------------
                case "input-url":
                    el = document.createElement("input");
                    el.type = "url";
                    el.classList.add("gm-object");
                    el.placeholder = "https://example.com";
                    // Optional: validasi pattern (harus mulai http:// atau https://)
                    el.pattern = "https?://.+";
                    el.required = true;
                    // Style
                    el.style.padding = "8px 12px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "250px";
                    el.style.margin = "0";
                    el.style.boxSizing = "border-box";

                    break;

                // input-search (input char untuk mencari sesuatu, perbedaannya terletak pada tanda silang pada bagian kanan nya untuk clear content)--------------------------------------------------------------------------------------------------------------
                case "input-search":
                    // main attr
                    el = document.createElement("input");
                    el.type = "search";
                    el.classList.add("gm-object");
                    el.placeholder = "Cari...";
                    // Style
                    el.style.padding = "8px 12px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "200px";
                    el.style.margin = "0";
                    el.style.boxSizing = "border-box";

                    break;

                // input-date (untuk memilih tanggal)--------------------------------------------------------------------------------------------------------------
                case "input-date":
                    el = document.createElement("input");
                    el.type = "date";
                    el.classList.add("gm-object");
                    // default value adalah current date (YYYY-MM-DD)
                    let today = new Date().toISOString().split("T")[0];
                    el.value = today;
                    // Style
                    el.style.padding = "8px 12px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "200px";
                    el.style.margin = "0";
                    el.style.boxSizing = "border-box";

                    break;

                // input-time (untuk memilih waktu)-------------------------------------------------------------------------------------------------------------------
                case "input-time":
                    // main attr
                    el = document.createElement("input");
                    el.type = "time";
                    el.classList.add("gm-object");
                    // set default value ke jam sekarang
                    let now1 = new Date();
                    let hours1 = now1.getHours().toString().padStart(2, "0");
                    let minutes1 = now1.getMinutes().toString().padStart(2, "0");
                    el.value = `${hours1}:${minutes1}`;
                    // Style
                    el.style.padding = "8px 12px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "200px";
                    el.style.margin = "0";
                    el.style.boxSizing = "border-box";

                    break;

                // input-datetime-local (input tanggal dan waktu bersamaan)-------------------------------------------------------------------------------------------------------------------
                case "input-datetime-local":
                    // main attr
                    el = document.createElement("input");
                    el.type = "datetime-local";
                    el.classList.add("gm-object");
                    // set default value ke tanggal & waktu sekarang
                    let now2 = new Date();
                    let year2 = now2.getFullYear();
                    let month2 = (now2.getMonth() + 1).toString().padStart(2, "0");
                    let day2 = now2.getDate().toString().padStart(2, "0");
                    let hours2 = now2.getHours().toString().padStart(2, "0");
                    let minutes2 = now2.getMinutes().toString().padStart(2, "0");
                    el.value = `${year2}-${month2}-${day2}T${hours2}:${minutes2}`;
                    // Style
                    el.style.padding = "8px 12px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "250px";
                    el.style.margin = "0";
                    el.style.boxSizing = "border-box";

                    break;

                // input-month (input untuk memasukkan bulan)-------------------------------------------------------------------------------------------------------------------
                case "input-month":
                    // main attr
                    el = document.createElement("input");
                    el.type = "month";
                    el.classList.add("gm-object");
                    // set default value ke bulan & tahun sekarang
                    const now3 = new Date();
                    const year3 = now3.getFullYear();
                    const month3 = (now3.getMonth() + 1).toString().padStart(2, "0");
                    el.value = `${year3}-${month3}`;
                    // Style
                    el.style.padding = "8px 12px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "200px";
                    el.style.margin = "0";
                    el.style.boxSizing = "border-box";

                    break;

                // input-week (input untuk memasukkan minggu ke-)-------------------------------------------------------------------------------------------------------------------
                case "input-week":
                    // main attr
                    el = document.createElement("input");
                    el.type = "week";
                    el.classList.add("gm-object");
                    // set default value ke minggu & tahun sekarang
                    let now4 = new Date();
                    let year4 = now4.getFullYear();
                    // Hitung minggu ke berapa
                    let firstDayOfYear = new Date(year4, 0, 1);
                    let pastDaysOfYear = (now4 - firstDayOfYear) / 86400000;
                    let weekNumber4 = Math.ceil((pastDaysOfYear + firstDayOfYear.getDay() + 1) / 7).toString().padStart(2, '0');
                    el.value = `${year4}-W${weekNumber4}`;
                    // Style
                    el.style.padding = "8px 12px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "200px";
                    el.style.margin = "0";
                    el.style.boxSizing = "border-box";

                    break;

                // input-checkbox (membuat element checkbox atau kotak yang bisa dipilih/tidak, mendukung multi select)-------------------------------------------------------------------------------------------------------------------
                case "input-checkbox":
                    // Buat label yang membungkus input
                    el = document.createElement("label");
                    el.classList.add("gm-object");
                    el.style.display = "inline-flex";
                    el.style.alignItems = "center";
                    el.style.cursor = "pointer";
                    // Checkbox
                    let checkbox = document.createElement("input");
                    checkbox.classList.add("gm-object");
                    checkbox.type = "checkbox";
                    checkbox.style.marginRight = "8px";
                    // Text editable
                    let span = document.createElement("span");
                    span.textContent = "Centang saya";
                    span.classList.add("gm-object");
                    span.contentEditable = "true";
                    // span.style.minWidth = "50px";

                    el.appendChild(checkbox);
                    el.appendChild(span);

                    break;

                // input-radio (membuat pilihan single select, hanya bisa dipilih satu)
                case "input-radio":
                    el = document.createElement("label");
                    el.classList.add("gm-object");
                    el.style.display = "inline-flex";
                    el.style.alignItems = "center";
                    el.style.cursor = "pointer";
                    el.style.margin = "4px 0";
                    // Radio button
                    let radio = document.createElement("input");
                    radio.classList.add("gm-object");
                    radio.type = "radio";
                    radio.name = "radio-group"; // beri name agar satu grup
                    radio.style.marginRight = "8px";
                    // Text editable
                    let span1 = document.createElement("span");
                    span1.classList.add("gm-object");
                    span1.textContent = "Default";
                    span1.contentEditable = "true";

                    el.appendChild(radio);
                    el.appendChild(span1);

                    break;

                // input-file (input untuk memasukkan file, choose file)
                case "input-file":
                    el = document.createElement("input");
                    el.type = "file";
                    el.classList.add("gm-object");
                    // Style
                    el.style.padding = "6px 10px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "220px";
                    el.style.margin = "0";
                    el.style.boxSizing = "border-box";

                    // Accept tetap ada tapi dikosongkan
                    el.accept = "";

                    break;

                // input-color (input untuk memilih color)
                case "input-color":
                    // main attr
                    el = document.createElement("input");
                    el.type = "color";
                    el.classList.add("gm-object");
                    // Nilai default
                    el.value = "#4a84b7";
                    // Style
                    el.style.padding = "0"; // color picker biasanya kotak kecil
                    el.style.width = "50px";
                    el.style.height = "30px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.margin = "0";
                    el.style.boxSizing = "border-box";

                    break;

                // input-range (memungkinkan pengguna memilih nilai dalam rentang tertentu dengan cara menggeser tuas (handle))-------------------------------------------------------------------------------------------------------------------
                case "input-range":
                    // main attr
                    el = document.createElement("input");
                    el.type = "range";
                    el.classList.add("gm-object");
                    // Atur atribut range default
                    el.min = "0";
                    el.max = "100";
                    el.step = "1";
                    el.value = "50";
                    // Style
                    el.style.width = "200px";
                    el.style.margin = "0";
                    el.style.padding = "0";
                    el.style.borderRadius = "4px";
                    el.style.background = "#4a84b7";
                    el.style.cursor = "pointer";

                    break;

                // input-hidden (untuk membuat elemen input tersembunyi, namun tetap bisa dikirim value nya ketika form dikirim)-------------------------------------------------------------------------------------------------------------------
                case "input-hidden":
                    // main attr
                    el = document.createElement("input");
                    el.type = "hidden"; // tetap hidden secara default
                    el.classList.add("gm-object");
                    // Optional: nilai default
                    el.value = "secret";

                    break;

                // input-submit (membuat tombol kirim (submit button) dalam sebuah <form> di HTML)-------------------------------------------------------------------------------------------------------------------
                case "input-submit":
                    // main attr
                    el = document.createElement("button");
                    el.type = "submit";
                    el.classList.add("gm-object");
                    // style
                    el.style.padding = "8px 16px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.backgroundColor = "#4a84b7";
                    el.style.color = "#fff";
                    el.style.cursor = "pointer";
                    el.style.margin = "0";
                    el.style.boxSizing = "border-box";
                    // Text editable
                    let span3 = document.createElement("span");
                    span3.textContent = "Submit";
                    span3.contentEditable = true;
                    span3.style.outline = "none";
                    el.appendChild(span3);

                    break;

                // input-reset (tombol yang akan mengembalikan semua nilai di dalam form ke kondisi awalnya)-------------------------------------------------------------------------------------------------------------------
                case "input-reset":
                    // main attr
                    el = document.createElement("button");
                    el.type = "reset";
                    el.classList.add("gm-object");
                    // Style tombol
                    el.style.padding = "8px 16px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.backgroundColor = "#f44336"; // merah untuk reset
                    el.style.color = "#fff";
                    el.style.cursor = "pointer";
                    el.style.margin = "0";
                    el.style.boxSizing = "border-box";
                    // Text editable
                    let span2 = document.createElement("span");
                    span2.textContent = "Reset";
                    span2.contentEditable = true;
                    span2.style.outline = "none";
                    el.appendChild(span2);

                    break;

                // input-button (membuat tombol biasa (custom button) di HTML, tidak menjalankan apapun secara default, tapi bisa menjalankan fungsi JS)-------------------------------------------------------------------------------------------------------------------
                case "input-button":
                    // main attr
                    el = document.createElement("button");
                    el.type = "button";
                    el.classList.add("gm-object");
                    // Style
                    el.style.padding = "8px 16px";
                    el.style.fontSize = "16px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.backgroundColor = "#5591ccff"; // merah untuk reset
                    el.style.color = "#fff";
                    el.style.cursor = "pointer";
                    el.style.margin = "0";
                    el.style.boxSizing = "border-box";
                    // Text editable
                    let span4 = document.createElement("span");
                    span4.textContent = "Button";
                    span4.contentEditable = true;
                    span4.style.outline = "none";
                    el.appendChild(span4);

                    break;

                // textarea (membuat kotak input teks multi-baris, yaitu tempat pengguna bisa menulis teks panjang, seperti komentar, deskripsi, atau pesan)-------------------------------------------------------------------------------------------------------------------
                case "textarea":
                    // main attr
                    el = document.createElement("textarea");
                    el.classList.add("gm-object");
                    el.placeholder = "Tulis sesuatu...";
                    el.rows = 4;
                    el.cols = 30;
                    // style
                    el.style.padding = "8px 10px";
                    el.style.fontSize = "14px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "220px";
                    el.style.resize = "vertical";

                    break;

                // select (membuat menu dropdown, yaitu elemen yang memungkinkan pengguna memilih satu atau beberapa opsi dari daftar)-------------------------------------------------------------------------------------------------------------------
                case "select":
                    // main attr
                    el = document.createElement("select");
                    el.classList.add("gm-object");
                    // style
                    el.style.padding = "6px 10px";
                    el.style.fontSize = "14px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.width = "160px";
                    // contoh isi awal
                    const optgroup = document.createElement("optgroup");
                    optgroup.classList.add("gm-object");
                    optgroup.label = "Contoh Grup";

                    const opt1 = document.createElement("option");
                    opt1.textContent = "Opsi 1";
                    opt1.classList.add("gm-object");
                    const opt2 = document.createElement("option");
                    opt2.classList.add("gm-object");
                    opt2.textContent = "Opsi 2";

                    optgroup.appendChild(opt1);
                    optgroup.appendChild(opt2);
                    el.appendChild(optgroup);

                    const opt3 = document.createElement("option");
                    opt3.classList.add("gm-object");
                    opt3.textContent = "Opsi 3";
                    el.appendChild(opt3);

                    break;

                // option (mendefinisikan satu pilihan (opsi) dalam menu dropdown, listbox, atau combo box di HTML)-------------------------------------------------------------------------------------------------------------------
                case "option":
                    // main attr
                    el = document.createElement("option");
                    el.classList.add("gm-object");
                    el.textContent = "Pilihan";
                    el.value = "pilihan";
                    el.contentEditable = "true";
                    el.style.padding = "2px 6px";
                    break;


                // optgroup (mengelompokkan beberapa <option> di dalam elemen <select>)-------------------------------------------------------------------------------------------------------------------
                case "optgroup":
                    // main attr
                    el = document.createElement("optgroup");
                    el.classList.add("gm-object");
                    el.label = "Nama Grup";
                    // style
                    el.style.display = "block";
                    el.style.padding = "4px 0";

                    break;

                // button (membuat tombol interaktif di HTML, Berbeda dengan <input type="button">, elemen <button> lebih fleksibel karena bisa berisi teks, ikon, atau elemen HTML lainnya di dalamnya)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "button":
                    // main attr
                    el = document.createElement("button");
                    el.classList.add("gm-object");
                    el.type = "button";
                    el.contentEditable = "true";
                    el.textContent = "Tombol";
                    // style
                    el.style.padding = "6px 14px";
                    el.style.fontSize = "14px";
                    el.style.border = "1px solid #4a84b7ff";
                    el.style.borderRadius = "6px";
                    el.style.background = "#eaf3ff";
                    el.style.cursor = "pointer";

                    break;

                // label (memberi keterangan atau judul pada elemen input form seperti <input>, <textarea>, <select>)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "label":
                    // main attr
                    el = document.createElement("label");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Label teks";
                    // style
                    el.style.fontSize = "14px";
                    el.style.cursor = "text";

                    break;

                // output (untuk menampilkan hasil perhitungan atau nilai yang dihasilkan secara otomatis (biasanya dari JavaScript atau input <form>))--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "output":
                    // main attr
                    el = document.createElement("output");
                    el.classList.add("gm-object");
                    el.textContent = "0";
                    // style
                    el.style.display = "inline-block";
                    el.style.padding = "4px 8px";
                    el.style.border = "1px solid #ccc";
                    el.style.borderRadius = "6px";
                    el.style.minWidth = "40px";
                    el.style.textAlign = "center";

                    break;

                // progress (untuk menampilkan kemajuan (progress) dari suatu proses seperti mengunduh file, memuat data, atau menyelesaikan tugas)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "progress":
                    // main attr
                    el = document.createElement("progress");
                    el.classList.add("gm-object");
                    el.value = 60;
                    el.max = 100;
                    el.textContent = "60%";
                    // style
                    el.style.width = "200px";
                    el.style.height = "18px";
                    break;

                // meter (menampilkan nilai dalam suatu rentang (range) seperti level baterai, suhu, volume suara, kekuatan sinyal, atau tingkat performa)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "meter":
                    // main attr
                    el = document.createElement("meter");
                    el.classList.add("gm-object");
                    el.value = 0.7;
                    el.min = 0;
                    el.max = 1;
                    el.low = 0.3;
                    el.high = 0.8;
                    el.optimum = 0.7;
                    // style
                    el.style.width = "200px";
                    el.style.height = "18px";

                    break;

                // datalist (membuat daftar saran (autocomplete) pada elemen <input>)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "datalist":
                    // main attr
                    let wrapper = document.createElement("div");
                    wrapper.classList.add("gm-object");
                    // style
                    wrapper.style.display = "inline-flex";
                    wrapper.style.flexDirection = "column";
                    wrapper.style.gap = "4px";

                    let input = document.createElement("input");
                    input.classList.add("gm-object");
                    input.placeholder = "Ketik untuk lihat opsi...";
                    input.style.padding = "6px 10px";
                    input.style.border = "1px solid #4a84b7ff";
                    input.style.borderRadius = "6px";
                    input.style.width = "200px";

                    let listId = "list-" + Math.floor(Math.random() * 9999);
                    input.setAttribute("list", listId);

                    let datalist = document.createElement("datalist");
                    datalist.classList.add("gm-object");
                    datalist.id = listId;

                    let optA = document.createElement("option");
                    optA.value = "Chrome";
                    optA.classList.add("gm-object");
                    let optB = document.createElement("option");
                    optB.value = "Firefox";
                    optB.classList.add("gm-object");
                    let optC = document.createElement("option");
                    optC.value = "Edge";
                    optC.classList.add("gm-object");
                    datalist.append(optA, optB, optC);
                    datalist.append(optA, optB, optC);

                    wrapper.append(input, datalist);
                    el = wrapper;

                    break;

                // TEXT ELEMENT 
                // -----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                // h1-h6 (membuat judul atau subjudul di halaman web)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "h1":
                case "h2":
                case "h3":
                case "h4":
                case "h5":
                case "h6":
                    el = document.createElement(type);
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = type.toUpperCase() + " Judul";
                    // style langsung biar beda tiap level
                    const sizeMap = {
                        h1: "28px",
                        h2: "24px",
                        h3: "20px",
                        h4: "18px",
                        h5: "16px",
                        h6: "14px"
                    };
                    el.style.fontSize = sizeMap[type];
                    el.style.fontWeight = "600";
                    el.style.width = "fit-content"
                    el.style.padding = "2px 4px";
                    el.style.color = "#1a1a1a";
                    el.style.lineHeight = "1.3";

                    break;

                // p (untuk membuat paragraf teks dalam HTML)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "p":
                    // main attr
                    el = document.createElement("p");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Tulis paragraf di sini...";
                    // style
                    el.style.fontSize = "14px";
                    el.style.lineHeight = "1.6";
                    el.style.color = "#222";
                    el.style.padding = "2px 4px";

                    break;

                // span (digunakan untuk membungkus (wrap) bagian kecil dari teks atau elemen tanpa makna semantik khusus)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "span":
                    // main attr
                    el = document.createElement("span");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Teks span";
                    // style
                    el.style.display = "inline";
                    el.style.fontSize = "14px";
                    el.style.color = "#222";
                    el.style.padding = "0 2px";

                    break;

                // strong (untuk menandai teks yang sangat penting, secara default tampil tebal (bold) di browser, tapi yang terpenting: <strong> punya makna semantik, bukan hanya tampilan)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "strong":
                    el = document.createElement("strong");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Teks penting";
                    el.style.fontWeight = "bold";
                    el.style.color = "#000";
                    el.style.padding = "0 2px";

                    break;

                // em (Menunjukkan penekanan makna pada teks (biasanya ditampilkan miring)(semantik))--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "em":
                    // main attr
                    el = document.createElement("em");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Teks penekanan";
                    // style
                    el.style.fontStyle = "italic";
                    el.style.color = "#333";
                    el.style.padding = "0 2px";

                    break;

                // b (Menampilkan teks dalam format tebal tanpa memberi makna khusus — hanya tampilan visual) (non-semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "b":
                    // main attr
                    el = document.createElement("b");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Teks tebal";
                    // style
                    el.style.fontWeight = "bold";
                    el.style.color = "#111";
                    el.style.padding = "0 2px";

                    break;

                // i (Menampilkan teks miring tanpa memberikan makna penekanan — hanya untuk gaya visual) (non-semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                // case "i":
                //     // main attr
                //     el = document.createElement("i");
                //     el.classList.add("gm-object");
                //     el.contentEditable = "true";
                //     el.textContent = "Teks miring";
                //     // style
                //     el.style.fontStyle = "italic";
                //     el.style.color = "#222";
                //     el.style.padding = "0 2px";

                //     break;

                // u (Menampilkan teks dengan garis bawah tanpa memberi makna tambahan — hanya untuk tampilan visual) (non-semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "u":
                    // main attr
                    el = document.createElement("u");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Teks bergaris bawah";
                    // style
                    el.style.textDecoration = "underline";
                    el.style.textUnderlineOffset = "2px";
                    el.style.color = "#222";
                    el.style.padding = "0 2px";

                    break;

                // small (Menampilkan teks dengan ukuran lebih kecil untuk keterangan tambahan atau catatan samping) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "small":
                    // main attr
                    el = document.createElement("small");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Teks kecil";
                    // style
                    el.style.fontSize = "12px";
                    el.style.color = "#555";
                    el.style.padding = "0 2px";

                    break;

                // mark (Menyorot atau menandai teks penting dengan latar warna kuning seperti stabilo) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "mark":
                    // main attr
                    el = document.createElement("mark");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Teks disorot";
                    // style
                    el.style.backgroundColor = "#ffeb3b";
                    el.style.color = "#000";
                    el.style.padding = "0 2px";
                    el.style.borderRadius = "3px";

                    break;

                // abbr (Menandai singkatan atau kependekan dan memberikan keterangan lengkap melalui atribut title) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "abbr":
                    // main attr
                    el = document.createElement("abbr");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "HTML";
                    el.title = "HyperText Markup Language";
                    // style
                    el.style.borderBottom = "1px dotted #666";
                    el.style.cursor = "help";
                    el.style.padding = "0 2px";

                    break;

                // cite (Menunjukkan judul karya, referensi, atau sumber kutipan) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "cite":
                    // main attr
                    el = document.createElement("cite");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Judul Karya atau Referensi";
                    el.title = "Sumber atau judul karya";
                    // style
                    el.style.fontStyle = "italic";
                    el.style.color = "#333";
                    el.style.display = "inline";
                    el.style.padding = "0 2px";

                    break;

                // code (Menampilkan potongan kode pemrograman dengan font monospace agar mudah dibaca) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "code":
                    // main attr
                    el = document.createElement("code");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "console.log('Hello, world!');";
                    // style
                    el.style.fontFamily = "monospace";
                    el.style.backgroundColor = "#f4f4f4";
                    el.style.padding = "4px 6px";
                    el.style.borderRadius = "4px";
                    el.style.border = "1px solid #ddd";
                    el.style.display = "inline-block";
                    el.style.whiteSpace = "pre";
                    el.style.fontSize = "14px";
                    el.style.color = "#333";

                    break;

                // pre (Menampilkan teks dengan format dan spasi yang dipertahankan persis seperti di dalam HTML, cocok untuk kode atau data terformat) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "pre":
                    // main attr
                    el = document.createElement("pre");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Teks dengan spasi dan format tetap\n    Baris 2\n        Baris 3";
                    // style
                    el.style.fontFamily = "monospace";
                    el.style.backgroundColor = "#f7f7f7";
                    el.style.padding = "10px 12px";
                    el.style.border = "1px solid #ddd";
                    el.style.borderRadius = "6px";
                    el.style.whiteSpace = "pre";
                    el.style.overflowX = "auto";
                    el.style.color = "#333";
                    el.style.fontSize = "14px";
                    el.style.display = "block";

                    break;

                // samp (Menunjukkan contoh keluaran dari program komputer atau sistem) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "samp":
                    // main attr
                    el = document.createElement("samp");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Program output: Operation completed successfully.";
                    // style
                    el.style.display = "inline-block";
                    el.style.fontFamily = "monospace";
                    el.style.backgroundColor = "#f4f4f4";
                    el.style.padding = "6px 10px";
                    el.style.border = "1px solid #ccc";
                    el.style.borderRadius = "6px";
                    el.style.color = "#222";
                    el.style.fontSize = "14px";
                    el.style.whiteSpace = "pre-wrap";

                    break;

                // kbd (Menunjukkan input dari pengguna, biasanya berupa tombol keyboard) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "kbd":
                    // main attr
                    el = document.createElement("kbd");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Ctrl + C";
                    // style
                    el.style.display = "inline-block";
                    el.style.fontFamily = "monospace";
                    el.style.backgroundColor = "#f0f0f0";
                    el.style.border = "1px solid #ccc";
                    el.style.borderRadius = "4px";
                    el.style.padding = "3px 6px";
                    el.style.margin = "0 2px";
                    el.style.fontSize = "13px";
                    el.style.boxShadow = "inset 0 -1px 0 #bbb";
                    el.style.color = "#333";

                    break;

                // sub (Menampilkan teks lebih kecil sedikit di bawah garis dasar, biasanya untuk rumus kimia atau matematika) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "sub":
                    // main attr
                    el = document.createElement("sub");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "2";
                    // style
                    el.style.fontSize = "smaller";
                    el.style.verticalAlign = "sub";
                    el.style.color = "#222";
                    el.style.margin = "0 2px";

                    break;

                // sup (Menampilkan teks lebih kecil sedikit di atas garis dasar, biasanya untuk pangkat atau catatan kaki) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "sup":
                    // main attr
                    el = document.createElement("sup");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "2";
                    // style
                    el.style.fontSize = "smaller";
                    el.style.verticalAlign = "super";
                    el.style.color = "#222";
                    el.style.margin = "0 2px";

                    break;

                // time (Menandai waktu atau tanggal tertentu dalam format yang dapat dibaca manusia dan mesin melalui atribut datetime) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "time":
                    // main attr
                    el = document.createElement("time");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "15 Oktober 2025";
                    el.setAttribute("datetime", "2025-10-15");
                    // style
                    el.style.display = "inline-block";
                    el.style.padding = "2px 6px";
                    el.style.border = "1px solid #ccc";
                    el.style.borderRadius = "4px";
                    el.style.backgroundColor = "#f9f9f9";
                    el.style.fontSize = "14px";
                    el.style.color = "#333";
                    el.style.fontFamily = "sans-serif";

                    break;

                // data (Menghubungkan konten teks dengan nilai data terstruktur menggunakan atribut value) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "data":
                    // main attr
                    el = document.createElement("data");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Contoh Data";
                    el.setAttribute("value", "data-001");
                    // style
                    el.style.display = "inline-block";
                    el.style.padding = "2px 6px";
                    el.style.border = "1px solid #ccc";
                    el.style.borderRadius = "4px";
                    el.style.backgroundColor = "#f5f5f5";
                    el.style.fontSize = "14px";
                    el.style.color = "#333";
                    el.style.fontFamily = "sans-serif";

                    break;

                // q (Menandai kutipan pendek dalam satu paragraf, browser biasanya menambahkan tanda kutip otomatis) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "q":
                    // main attr
                    el = document.createElement("q");
                    el.classList.add("gm-object");
                    el.contentEditable = "true";
                    el.textContent = "Isi kutipan di sini...";
                    // style
                    el.style.fontStyle = "italic";
                    el.style.color = "#333";
                    el.style.padding = "2px 4px";
                    el.style.borderLeft = "2px solid #ccc";
                    el.style.margin = "0 4px";
                    el.style.display = "inline";

                    break;

                // br (Membuat pemisah baris dalam teks, memaksa konten berikutnya ke baris baru) (non-semantik, hanya struktural)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "br":
                    el = document.createElement("br");
                    // Tidak memiliki teks, atribut, atau gaya—murni untuk memecah baris.
                    break;

                // wbr (Menentukan posisi opsional di mana browser boleh memutus kata jika perlu untuk pengaturan baris) (non-semantik, fungsional)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "wbr":
                    el = document.createElement("wbr");
                    // Tidak terlihat secara visual dan tidak bisa diedit (fungsi internal pemisah kata).
                    break;

                // INLNE ELEMENT ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                // ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

                // a (Mendefinisikan hyperlink untuk menghubungkan ke halaman, file, atau lokasi lain di dokumen) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "a":
                    // main attr
                    el = document.createElement("a");
                    el.href = "#";
                    el.textContent = "Klik di sini";
                    el.target = "_blank";
                    el.contentEditable = "true";
                    // style
                    el.style.color = "#007bff";
                    el.style.textDecoration = "underline";
                    el.style.cursor = "pointer";
                    el.style.padding = "0 2px";

                    break;

                // img (Menampilkan gambar di halaman web menggunakan sumber eksternal dari atribut src) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "img":
                    // main attr
                    el = document.createElement("img");
                    el.src = "https://via.placeholder.com/150";
                    el.alt = "Contoh Gambar";
                    // style
                    el.style.display = "block";
                    el.style.maxWidth = "100%";
                    el.style.borderRadius = "6px";
                    el.style.margin = "4px 0";

                    break;

                // picture (Menampung beberapa sumber gambar responsif untuk berbagai kondisi layar) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "picture":
                    // main attr
                    el = document.createElement("picture");
                    const src = document.createElement("source");
                    src.srcset = "https://via.placeholder.com/600";
                    src.media = "(min-width: 600px)";
                    const img = document.createElement("img");
                    img.src = "https://via.placeholder.com/300";
                    img.alt = "Responsif";
                    el.append(src, img);
                    // style
                    el.style.display = "block";
                    el.style.margin = "4px 0";

                    break;

                // audio (Memutar berkas audio di halaman dengan kontrol pemutar) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "audio":
                    // main attr
                    el = document.createElement("audio");
                    el.controls = true;
                    const srcAudio = document.createElement("source");
                    srcAudio.src = "audio.mp3";
                    srcAudio.type = "audio/mpeg";
                    el.appendChild(srcAudio);
                    // style
                    el.style.display = "block";
                    el.style.margin = "6px 0";

                    break;

                // video (Menampilkan konten video di halaman dengan kontrol pemutaran) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "video":
                    // main attr
                    el = document.createElement("video");
                    el.controls = true;
                    el.width = 320;
                    const srcVid = document.createElement("source");
                    srcVid.src = "video.mp4";
                    srcVid.type = "video/mp4";
                    el.appendChild(srcVid);
                    // style
                    el.style.display = "block";
                    el.style.margin = "6px 0";
                    el.style.borderRadius = "8px";
                    el.style.background = "#000";

                    break;

                // svg (Mendefinisikan grafik vektor berbasis XML yang bisa diskalakan tanpa kehilangan kualitas) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "svg":
                    // main attr
                    el = document.createElementNS("http://www.w3.org/2000/svg", "svg");
                    el.setAttribute("width", "100");
                    el.setAttribute("height", "100");
                    const circle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                    circle.setAttribute("cx", "50");
                    circle.setAttribute("cy", "50");
                    circle.setAttribute("r", "40");
                    circle.setAttribute("fill", "skyblue");
                    el.appendChild(circle);
                    // style
                    el.style.display = "block";
                    el.style.margin = "6px 0";

                    break;

                // script (Menjalankan atau menyematkan kode JavaScript ke dalam halaman) (non-semantik, fungsional)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "script":
                    // main attr
                    el = document.createElement("script");
                    el.textContent = "console.log('Script berjalan');";
                    // style
                    el.style.display = "none"; // umumnya tidak terlihat di halaman
                    break;

                // noscript (Menampilkan konten alternatif bila JavaScript tidak aktif di browser) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "noscript":
                    // main attr
                    el = document.createElement("noscript");
                    el.textContent = "JavaScript tidak aktif di browser Anda.";
                    // style
                    el.style.display = "block";
                    el.style.color = "#d00";
                    el.style.background = "#fee";
                    el.style.padding = "6px";
                    el.style.borderRadius = "4px";
                    el.style.fontSize = "14px";

                    break;

                // link (Menghubungkan dokumen HTML dengan sumber eksternal seperti stylesheet atau favicon) (non-semantik, fungsional)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "link":
                    // main attr
                    el = document.createElement("link");
                    el.rel = "stylesheet";
                    el.href = "style.css";
                    // style
                    el.style.display = "none"; // tidak terlihat di halaman
                    break;

                // style (Menyematkan aturan CSS langsung ke dalam dokumen HTML) (non-semantik, fungsional)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "style":
                    // main attr
                    el = document.createElement("style");
                    el.textContent = "body { background: #f9f9f9; }";
                    // style
                    el.style.display = "none"; // tidak ditampilkan secara visual
                    break;

                // template (Mendefinisikan konten HTML yang tidak langsung dirender, tapi bisa digunakan kembali melalui JavaScript) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "template":
                    // main attr
                    el = document.createElement("template");
                    el.innerHTML = `<div>Isi template</div>`;
                    // style
                    el.style.display = "none"; // isi hanya aktif lewat JS
                    break;

                // slot (Menentukan tempat penampung konten dinamis di dalam Web Component) (semantik)--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                case "slot":
                    // main attr
                    el = document.createElement("slot");
                    el.name = "isi";
                    // style
                    el.style.display = "inline-block";
                    el.style.padding = "4px 6px";
                    el.style.border = "1px dashed #bbb";
                    el.style.borderRadius = "4px";
                    el.style.color = "#555";
                    el.style.background = "#fafafa";

                    break;

                default:
                    // jika type bukan tag HTML valid, default ke div
                    el = document.createElement("div");
                    // jangan gunakan <div> di textContent
                    el.textContent = type;
            }
        }

        el.classList.add("gm-object");
        el.dataset.type = type;

        makeDroppable(el);
        return el;
    }



});
