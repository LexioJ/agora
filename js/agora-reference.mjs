(function() {
  "use strict";
  try {
    if (typeof document != "undefined") {
      var elementStyle = document.createElement("style");
      elementStyle.appendChild(document.createTextNode("#body-user .badge-small[data-v-a75cba4f] {\n  display: flex;\n  flex: 0 0 fit-content;\n  align-items: center;\n  gap: 5px;\n  border: 2px solid;\n  border-radius: var(--border-radius-pill) !important;\n  text-align: center;\n  font-size: 0.9em;\n  overflow: hidden;\n  padding: 0px 8px !important;\n  margin: 0 !important;\n  min-height: 1.4rem;\n}\n#body-user .badge-small span[data-v-a75cba4f] {\n  overflow: hidden;\n  text-overflow: ellipsis;\n  white-space: nowrap;\n}\nh2 #body-user .badge-small[data-v-a75cba4f] {\n  font-size: 0.6em;\n}\n#body-user .badge-small.error[data-v-a75cba4f] {\n  background-color: rgba(var(--color-error-rgb), 0.2);\n  border-color: var(--color-error);\n}\n#body-user .badge-small.success[data-v-a75cba4f] {\n  background-color: rgba(var(--color-success-rgb), 0.2);\n  border-color: var(--color-success);\n}\n#body-user .badge-small.warning[data-v-a75cba4f] {\n  background-color: rgba(var(--color-warning-rgb), 0.2) !important;\n  border-color: var(--color-warning);\n}.agora_widget[data-v-148b2b73] {\n  padding: 0.6rem;\n}\n.widget_header[data-v-148b2b73],\n.widget_footer[data-v-148b2b73] {\n  display: flex;\n  column-gap: 0.3rem;\n}\n.badge-small[data-v-148b2b73] {\n  flex: 0;\n}\n.agora_app_icon[data-v-148b2b73] {\n  flex: 0 0 1.4rem;\n}\n.title[data-v-148b2b73] {\n  flex: 1;\n  font-weight: bold;\n  padding-inline-start: 0.6rem;\n  text-wrap: nowrap;\n  overflow: hidden;\n  text-overflow: ellipsis;\n}\n.description[data-v-148b2b73] {\n  margin-inline-start: 1.4rem;\n  padding: 0.6rem;\n}\n.owner[data-v-148b2b73] {\n  margin-inline-start: 1.4rem;\n  padding-inline-start: 0.6rem;\n}\n.clamped[data-v-148b2b73] {\n  display: -webkit-box !important;\n  -webkit-line-clamp: 4;\n  line-clamp: 4;\n  -webkit-box-orient: vertical;\n  text-wrap: wrap;\n  overflow: clip !important;\n  text-overflow: ellipsis !important;\n  padding: 0 !important;\n}/*!\n * SPDX-FileCopyrightText: 2025 Trappe Vincent \n * SPDX-License-Identifier: AGPL-3.0-or-later\n */\n.icon-agora {\n  background-image: url(../img/agora.svg);\n  filter: var(--background-invert-if-dark);\n}\n\n.icon-agora-dark {\n  background-image: url(../img/agora-dark.svg);\n  filter: var(--background-invert-if-dark);\n}"));
      document.head.appendChild(elementStyle);
    }
  } catch (e) {
    console.error("vite-plugin-css-injected-by-js", e);
  }
})();
const be = "agora", ge = "1.5.0";
import { m as I, d as g, B as O, C as R, j as c, I as M, R as D, J as A, e as p, o as i, w as o, r as f, G as w, K as j, f as v, a as d, c as l, z as m, u as n, L as E, M as P, l as u, O as T, _ as $, P as z, k, t as N, y as W, h as G, p as J } from "./ThumbIcon.vue_vue_type_style_index_0_scoped_3109c301_lang-CfCMm5F1.chunk.mjs";
import { r as K } from "./NcRichText-CETsOVBU-KZ5bQI-3.chunk.mjs";
import { _ as Z } from "./AgoraAppIcon.vue_vue_type_script_setup_true_lang-CN9UKC7P.chunk.mjs";

const F = {};
function Q(t, r) {
  return i(), l("div", null, [f(t.$slots, "trigger")]);
}
const X = I(F, [["render", Q]]), Y = { class: "user-bubble__name" }, x = { key: 0, class: "user-bubble__secondary" }, ee = g({ __name: "NcUserBubble", props: O({ avatarImage: { default: void 0 }, user: { default: void 0 }, displayName: { default: void 0 }, showUserStatus: { type: Boolean }, url: { default: void 0 }, to: { default: void 0 }, primary: { type: Boolean }, size: { default: 20 }, margin: { default: 2 } }, { open: { type: Boolean }, openModifiers: {} }), emits: O(["click"], ["update:open"]), setup(t, { emit: r }) {
  const e = R(t, "open"), a = t, C = r, _ = c(() => {
    if (!a.avatarImage) return false;
    try {
      return !!new URL(a.avatarImage);
    } catch {
      return false;
    }
  }), h = c(() => !!a.avatarImage), q = c(() => ({ marginInlineStart: `${a.margin}px` })), B = c(() => {
    if (!a.url || a.url.trim() === "") return false;
    try {
      return !!new URL(a.url, a.url?.startsWith?.("/") ? window.location.href : void 0);
    } catch {
      return M("[NcUserBubble] Invalid URL passed", { url: a.url }), false;
    }
  }), H = c(() => B.value ? a.url : void 0), S = c(() => B.value ? "a" : a.to ? D : "div"), U = c(() => ({ height: `${a.size}px`, lineHeight: `${a.size}px`, borderRadius: `${a.size / 2}px` }));
  return A([() => a.displayName, () => a.user], () => {
    !a.displayName && a.user;
  }), (s, y) => (i(), p(w(s.$slots.default ? n(T) : X), { shown: e.value, "onUpdate:shown": y[1] || (y[1] = (b) => e.value = b), class: "user-bubble__wrapper", trigger: "hover focus" }, { trigger: o(({ attrs: b }) => [(i(), p(w(S.value), j({ class: ["user-bubble__content", { "user-bubble__content--primary": s.primary }], style: U.value, to: s.to, href: H.value }, b, { onClick: y[0] || (y[0] = (L) => C("click", L)) }), { default: o(() => [v(n(E), { url: h.value && _.value ? s.avatarImage : void 0, "icon-class": h.value && !_.value ? s.avatarImage : void 0, user: s.user, "display-name": s.displayName, size: s.size - s.margin * 2, style: P(q.value), "disable-tooltip": true, "disable-menu": true, "hide-status": !s.showUserStatus, class: "user-bubble__avatar" }, null, 8, ["url", "icon-class", "user", "display-name", "size", "style", "hide-status"]), d("span", Y, u(s.displayName || s.user), 1), s.$slots.name ? (i(), l("span", x, [f(s.$slots, "name", {}, void 0, true)])) : m("", true)]), _: 2 }, 1040, ["class", "style", "to", "href"]))]), default: o(() => [f(s.$slots, "default", {}, void 0, true)]), _: 3 }, 40, ["shown"]));
} }), ae = I(ee, [["__scopeId", "data-v-37bde6b7"]]), se = { name: "CalendarEndIcon", emits: ["click"], props: { title: { type: String }, fillColor: { type: String, default: "currentColor" }, size: { type: Number, default: 24 } } }, te = ["aria-hidden", "aria-label"], re = ["fill", "width", "height"], ie = { d: "M22 14V22H20V18L16 22V19H11V17H16V14L20 18V14H22M5 19L9 19V21L5 21C3.9 21 3 20.1 3 19V5C3 3.89 3.9 3 5 3H6V.998H8V3H16V.998H18V3H19C20.11 3 21 3.89 21 5L21 12H19V8H5V19Z" }, ne = { key: 0 };
function oe(t, r, e, a, C, _) {
  return i(), l("span", j(t.$attrs, { "aria-hidden": e.title ? null : "true", "aria-label": e.title, class: "material-design-icon calendar-end-icon", role: "img", onClick: r[0] || (r[0] = (h) => t.$emit("click", h)) }), [(i(), l("svg", { fill: e.fillColor, class: "material-design-icon__svg", width: e.size, height: e.size, viewBox: "0 0 24 24" }, [d("path", ie, [e.title ? (i(), l("title", ne, u(e.title), 1)) : m("", true)])], 8, re))], 16, te);
}
const le = $(se, [["render", oe]]), ue = { key: 0 }, ce = g({ __name: "BadgeSmallDiv", props: { tag: { default: "span" } }, setup(t) {
  return (r, e) => (i(), p(w(r.tag), { class: "badge-small" }, { default: o(() => [f(r.$slots, "icon", {}, void 0, true), r.$slots.default ? (i(), l("span", ue, [f(r.$slots, "default", {}, void 0, true)])) : m("", true)]), _: 3 }));
} }), V = $(ce, [["__scopeId", "data-v-a75cba4f"]]), de = { key: 0, class: "agora_widget" }, pe = { class: "widget_header" }, me = ["href"], fe = { class: "description" }, he = { class: "clamped" }, ye = { key: 0, class: "widget_footer" }, ve = g({ __name: "Reference", props: { richObject: {} }, setup(t) {
  const r = t.richObject?.inquiry?.expiry ? z.fromMillis(t.richObject.inquiry.expiry * 1e3).diffNow("hours").hours < 36 ? "warning" : "success" : "";
  return (e, a) => e.richObject ? (i(), l("div", de, [d("div", pe, [v(n(Z), { size: 20, class: "title-icon" }), d("a", { class: "title", href: e.richObject.inquiry.url, target: "_blank" }, u(e.richObject.inquiry.title), 9, me), e.richObject.inquiry.participated ? (i(), p(V, { key: 0, class: "success" }, { default: o(() => [k(u(n(N)("agora", "participated")), 1)]), _: 1 })) : e.richObject.inquiry.expired ? (i(), p(V, { key: 1, class: "error" }, { default: o(() => [k(u(n(N)("agora", "closed")), 1)]), _: 1 })) : e.richObject.inquiry.expiry > 0 ? (i(), p(V, { key: 2, class: W(n(r)) }, { icon: o(() => [v(le, { size: 16 })]), default: o(() => [k(" " + u(n(z).fromMillis(e.richObject.inquiry.expiry * 1e3).toRelative()), 1)]), _: 1 }, 8, ["class"])) : m("", true)]), d("div", fe, [d("span", he, u(e.richObject.inquiry.description), 1)]), e.richObject.inquiry.ownerId ? (i(), l("div", ye, [d("span", null, u(n(N)("agora", "By")), 1), v(n(ae), { user: e.richObject.inquiry.ownerId, "display-name": e.richObject.inquiry.ownerDisplayName }, null, 8, ["user", "display-name"])])) : m("", true)])) : m("", true);
} }), _e = $(ve, [["__scopeId", "data-v-148b2b73"]]);
K("agora_reference_widget", async (t, { richObject: r }) => G(_e, { richObject: r }).use(J).mount(t), (t) => t.classList.add("nc-agora-reference-widget"), {});
//# sourceMappingURL=agora-reference.mjs.map
