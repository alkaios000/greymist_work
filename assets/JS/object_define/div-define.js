// document.addEventListener("DOMContentLoaded", () => {
//     // === DRAG SOURCE ===
//     document.querySelectorAll(".gm-object-dropable").forEach(t => {
//         t.setAttribute("draggable", "true");
//         t.addEventListener("dragstart", e => {
//             e.dataTransfer.setData("text/plain", t.dataset.type || "");
//             e.dataTransfer.effectAllowed = "copy";
//         });
//         t.addEventListener("dragstart", e => {
//             const type = (t.dataset.type || "div").toLowerCase();
//             e.dataTransfer.setData("text/plain", type);
//             e.dataTransfer.effectAllowed = "copy";
//         });

//     });

//  function createElementByType(type, iconClass = null) {
//   let el;

//   // Tangani icon khusus
//   if (type === "icon" && iconClass) {
//     el = document.createElement("div");
//     el.classList.add("gm-object");
//     const iElem = document.createElement("i");
//     iElem.className = iconClass;
//     iElem.style.fontSize = "2em";
//     el.appendChild(iElem);
//   } else {
//     switch (type) {
//       case "input-text":
//         el = document.createElement("input");
//         el.type = "text";
//         el.placeholder = "Input text";
//         break;
//       case "input-radio":
//         el = document.createElement("label");
//         const radio = document.createElement("input");
//         radio.type = "radio";
//         el.appendChild(radio);
//         el.appendChild(document.createTextNode(" Default"));
//         break;
//       case "textarea":
//         el = document.createElement("textarea");
//         el.textContent = "Textarea";
//         break;
//       case "select":
//         el = document.createElement("select");
//         el.innerHTML = "<option>Option 1</option><option>Option 2</option>";
//         break;
//       case "button":
//         el = document.createElement("button");
//         el.textContent = "Button";
//         break;
//       case "img":
//         el = document.createElement("img");
//         el.src = "https://via.placeholder.com/100x50";
//         el.alt = "Image";
//         break;
//       case "iframe":
//         el = document.createElement("iframe");
//         el.src = "about:blank";
//         el.width = "200";
//         el.height = "100";
//         break;
//       default:
//         const VALID_HTML_TAGS = ["div", "span", "header", "footer", "section", "p", "h1", "h2", "h3", "h4", "h5", "h6"];
//         if (VALID_HTML_TAGS.includes(type.toLowerCase())) {
//           el = document.createElement(type.toLowerCase());
//         } else {
//           el = document.createElement("div");
//         }

//     }
//   }

//         el.classList.add("gm-object");
//         el.dataset.type = type;

//         makeDroppable(el);
//         return el;
//     }

//     // === BUAT TARGET DROP ===
//     function makeDroppable(container) {
//         if (container.dataset.gmDroppable === "true") return;
//         container.dataset.gmDroppable = "true";

//         container.addEventListener("dragover", e => {
//             e.preventDefault();
//             e.dataTransfer.dropEffect = "copy";
//             container.classList.add("gm-drop-hover");
//         });

//         container.addEventListener("dragleave", e => {
//             if (!container.contains(e.relatedTarget)) {
//                 container.classList.remove("gm-drop-hover");
//             }
//         });

//         container.addEventListener("drop", e => {
//             e.preventDefault();
//             e.stopPropagation(); // cegah parent ikut menangani drop
//             container.classList.remove("gm-drop-hover");

//             const type = e.dataTransfer.getData("text/plain");
//             if (!type) return;

//             // === Deteksi apakah drop dilakukan di area child atau di core ===
//             const dropTarget = e.target.closest(".gm-object, .gm-object-core");

//             // jika drop di dalam elemen yang lebih spesifik (bukan container saat ini), abaikan
//             if (dropTarget && dropTarget !== container) return;

//             // === Buat elemen baru sesuai tipe ===
//             const newEl = createElementByType(type);

//             // === Terapkan droppable ke elemen baru (biar bisa nested) ===
//             makeDroppable(newEl);

//             // === Masukkan ke dalam container ===
//             container.appendChild(newEl);
//         });
//     }


//     // === AKTIFKAN UNTUK SEMUA CORE YANG ADA ===
//     // --- Aktifkan droppable untuk semua core yang ada ---
//     document.querySelectorAll('.gm-object-core').forEach(core => makeDroppable(core));

//     // --- Pantau kalau ada core baru yang ditambahkan ke DOM ---
//     const observer = new MutationObserver(mutations => {
//         mutations.forEach(mutation => {
//             mutation.addedNodes.forEach(node => {
//                 // Jika node baru adalah object core baru
//                 if (node.classList && node.classList.contains('gm-object-core')) {
//                     makeDroppable(node);
//                 }
//                 // Jika ada object core di dalam node (misal dimasukkan via innerHTML)
//                 else if (node.querySelectorAll) {
//                     node.querySelectorAll('.gm-object-core').forEach(inner => makeDroppable(inner));
//                 }
//             });
//         });
//     });

//     observer.observe(document.body, {
//         childList: true,
//         subtree: true
//     });



// });



// function reInitDroppableCore(core) {
//     if (!core) return;

//     // Hapus semua flag dataset droppable
//     core.removeAttribute("data-gm-droppable");
//     core.querySelectorAll("[data-gm-droppable]").forEach(el => el.removeAttribute("data-gm-droppable"));

//     // Terapkan listener ke core
//     makeDroppable(core);

//     // Terapkan listener ke semua nested object
//     core.querySelectorAll(".gm-object").forEach(obj => {
//         makeDroppable(obj);
//     });
// }


// // Observer untuk future children
// const observer = new MutationObserver(mutations => {
//     mutations.forEach(m => {
//         m.addedNodes.forEach(node => {
//             if (!(node instanceof HTMLElement)) return;
//             if (node.classList.contains('gm-object')) makeDroppable(node);
//             node.querySelectorAll('.gm-object').forEach(child => makeDroppable(child));
//         });
//     });
// });

// observer.observe(document.body, { childList: true, subtree: true });
