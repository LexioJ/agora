(function() {
  "use strict";
  try {
    if (typeof document != "undefined") {
      var elementStyle = document.createElement("style");
      elementStyle.appendChild(document.createTextNode(".inquiry-item__item[data-v-150baf40] {\n  display: flex;\n  padding: 4px 0;\n}\n.inquiry-item__item.active[data-v-150baf40] {\n  background-color: var(--color-primary-element-light);\n}\n.inquiry-item__item[data-v-150baf40]:hover {\n  background-color: var(--color-background-hover);\n}\n.item__title[data-v-150baf40] {\n  display: flex;\n  flex-direction: column;\n  overflow: hidden;\n}\n.item__title[data-v-150baf40] * {\n  display: block;\n  overflow: hidden;\n  white-space: nowrap;\n  text-overflow: ellipsis;\n}\n.item__title .item__title__description[data-v-150baf40] {\n  opacity: 0.5;\n}\n.item__icon-spacer[data-v-150baf40] {\n  width: 44px;\n  min-width: 44px;\n}/*!\n * SPDX-FileCopyrightText: 2025 Trappe Vincent \n * SPDX-License-Identifier: AGPL-3.0-or-later\n */\n.icon-agora {\n  background-image: url(../img/agora.svg);\n  filter: var(--background-invert-if-dark);\n}\n\n.icon-agora-dark {\n  background-image: url(../img/agora-dark.svg);\n  filter: var(--background-invert-if-dark);\n}"));
      document.head.appendChild(elementStyle);
    }
  } catch (e) {
    console.error("vite-plugin-css-injected-by-js", e);
  }
})();
const H = "agora", P = "1.5.4";
import { d as f, t as o, j as b, b as q, c as v, o as d, f as p, w as _, a as t, u as e, E as D, e as I, G as w, l as u, H as C, _ as L, h as N, p as S } from "./ThumbIcon.vue_vue_type_style_index_0_scoped_3109c301_lang-DFqwmrph.chunk.mjs";
import { a as x, b as M, N as T, L as E, s as A, I as l } from "./NcDashboardWidget-Bu7bWoUK-DBaDMARu.chunk.mjs";
import { _ as B } from "./AgoraAppIcon.vue_vue_type_script_setup_true_lang-CW-He0tn.chunk.mjs";
import { g as k } from "./InquiryHelper-CceUyEr8.chunk.mjs";

const V = ["href"], F = { class: "inquiry-item__item" }, G = { class: "type-icon" }, O = { class: "item__title" }, W = { class: "item__title__title" }, $ = { class: "item__title__description" }, j = f({ __name: "Dashboard", setup(n) {
  const r = x(), c = { emptyContentMessage: o("agora", "No inquiries found for this category"), showMoreText: o("agora", "Relevant inquiries") }, m = b(() => r.appSettings.inquiryTypeTab || []), i = M();
  function g() {
    E.debug("Loading inquiries in dashboard widget");
    try {
      i.load();
    } catch {
      A(o("agora", "Error setting dashboard list"));
    }
  }
  function h(s) {
    return s.type ? k(s.type, m.value)?.icon || l.Flash : l.Flash;
  }
  return q(() => {
    g();
  }), (s, y) => (d(), v("div", null, [p(e(T), { items: e(i).dashboardList, "empty-content-message": c.emptyContentMessage, "show-more-text": c.showMoreText, loading: e(i).inquiriesLoading }, { emptyContentIcon: _(() => [p(e(B))]), default: _(({ item: a }) => [t("a", { href: e(D)(`/apps/agora/inquiry/${a.id}`) }, [t("div", F, [t("div", G, [(d(), I(w(h(s.inquiry)), { class: "nav-icon" }))]), t("div", O, [t("div", W, u(a.title), 1), t("div", $, u(e(C).sanitize(a.description ? a.description : e(o)("agora", "No description provided"))), 1)])])], 8, V)]), _: 1 }, 8, ["items", "empty-content-message", "show-more-text", "loading"])]));
} }), z = L(j, [["__scopeId", "data-v-150baf40"]]);
document.addEventListener("DOMContentLoaded", () => {
  OCA.Dashboard.register("agora", (n) => N(z).use(S).mount(n));
});
//# sourceMappingURL=agora-dashboard.mjs.map
