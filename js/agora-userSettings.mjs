const v = "agora", w = "1.5.0-beta";
import { a as c, t as a, e as m, f as l, o as f, w as s, g as t, u as e, n as r, h as n, i as g, p as u } from "./ThumbIcon.vue_vue_type_style_index_0_scoped_3109c301_lang-BHFm0nDv.chunk.mjs";
import { N as i } from "./index-DtuExfoC.chunk.mjs";
import "./NcDashboardWidget-Bu7bWoUK-DUUdNege.chunk.mjs";
import { F as _ } from "./FlexSettings-8HdKkfyo.chunk.mjs";
import { u as S } from "./preferences-F4p7Zmjk.chunk.mjs";
import "./NcRichText-CETsOVBU-D-QDiRZ9.chunk.mjs";
import { _ as d, a as y } from "./StyleSettings-DIrdsI19.chunk.mjs";
const P = c({ __name: "UserSettingsPage", setup(x) {
  const p = S(), o = { personalSettings: { name: a("agora", "Personal preferences"), description: a("agora", "Set your personal preferences for the agora app") }, styleSettings: { name: a("agora", "Experimental styles"), description: a("agora", "Some visual styling options.") } };
  return m(() => {
    p.load();
  }), (N, k) => (f(), l(e(_), null, { default: s(() => [t(e(i), r(n(o.personalSettings)), { default: s(() => [t(e(d))]), _: 1 }, 16), t(e(i), r(n(o.styleSettings)), { default: s(() => [t(e(y))]), _: 1 }, 16)]), _: 1 }));
} }), h = g(P).use(u);
h.mount("#content_agora");
//# sourceMappingURL=agora-userSettings.mjs.map
