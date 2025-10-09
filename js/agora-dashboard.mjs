(function() {
  "use strict";
  try {
    if (typeof document != "undefined") {
      var elementStyle = document.createElement("style");
      elementStyle.appendChild(document.createTextNode(".inquiry-item__item[data-v-b3352e26] {\n  display: flex;\n  padding: 4px 0;\n}\n.inquiry-item__item.active[data-v-b3352e26] {\n  background-color: var(--color-primary-element-light);\n}\n.inquiry-item__item[data-v-b3352e26]:hover {\n  background-color: var(--color-background-hover);\n}\n.item__title[data-v-b3352e26] {\n  display: flex;\n  flex-direction: column;\n  overflow: hidden;\n}\n.item__title[data-v-b3352e26] * {\n  display: block;\n  overflow: hidden;\n  white-space: nowrap;\n  text-overflow: ellipsis;\n}\n.item__title .item__title__description[data-v-b3352e26] {\n  opacity: 0.5;\n}\n.item__icon-spacer[data-v-b3352e26] {\n  width: 44px;\n  min-width: 44px;\n}/*!\n * SPDX-FileCopyrightText: 2025 Trappe Vincent \n * SPDX-License-Identifier: AGPL-3.0-or-later\n */\n.icon-agora {\n  background-image: url(../img/agora.svg);\n  filter: var(--background-invert-if-dark);\n}\n\n.icon-agora-dark {\n  background-image: url(../img/agora-dark.svg);\n  filter: var(--background-invert-if-dark);\n}"));
      document.head.appendChild(elementStyle);
    }
  } catch (e) {
    console.error("vite-plugin-css-injected-by-js", e);
  }
})();
const O = "agora", W = "1.5.0-beta";
import { d as m, s as o, v as g, c as h, o as n, j as d, g as c, k as s, n as e, D as l, f as u, h as f, t as _, E as y, _ as b, z as v, A as q } from "./NcEmptyContent-q-geAf0w-BKa4KW08.chunk.mjs";
import { a as D, I as C, N as w, L as x, s as I } from "./NcDashboardWidget-BEUtfCxs-BO6nrLPw.chunk.mjs";
import { _ as L } from "./AgoraAppIcon.vue_vue_type_script_setup_true_lang-y1rrm5Kr.chunk.mjs";

const N = ["href"], M = { class: "inquiry-item__item" }, A = { class: "type-icon" }, E = { class: "item__title" }, k = { class: "item__title__title" }, B = { class: "item__title__description" }, S = m({ __name: "Dashboard", setup(r) {
  const a = { emptyContentMessage: o("agora", "No inquiries found for this category"), showMoreText: o("agora", "Relevant inquiries") }, i = D();
  function p() {
    x.debug("Loading inquiries in dashboard widget");
    try {
      i.load();
    } catch {
      I(o("agora", "Error setting dashboard list"));
    }
  }
  return g(() => {
    p();
  }), (V, z) => (n(), h("div", null, [d(e(w), { items: e(i).dashboardList, "empty-content-message": a.emptyContentMessage, "show-more-text": a.showMoreText, loading: e(i).inquiriesLoading }, { emptyContentIcon: c(() => [d(e(L))]), default: c(({ item: t }) => [s("a", { href: e(l)(`/apps/agora/inquiry/${t.id}`) }, [s("div", M, [s("div", A, [(n(), u(f(e(C)[t.type].icon)))]), s("div", E, [s("div", k, _(t.title), 1), s("div", B, _(e(y).sanitize(t.description ? t.description : e(o)("agora", "No description provided"))), 1)])])], 8, N)]), _: 1 }, 8, ["items", "empty-content-message", "show-more-text", "loading"])]));
} }), T = b(S, [["__scopeId", "data-v-b3352e26"]]);
document.addEventListener("DOMContentLoaded", () => {
  OCA.Dashboard.register("agora", (r) => v(T).use(q).mount(r));
});
//# sourceMappingURL=agora-dashboard.mjs.map
