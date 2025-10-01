const k = "agora", v = "1.5.0-beta";
import { d as c, t as a, b as m, e as l, o as g, w as s, f as t, u as e, n as r, g as n, h as f, p as u } from "./NcEmptyContent-q-geAf0w-BlFSKrqr.chunk.mjs";
import { N as i } from "./index-B4axEyfl.chunk.mjs";
import "./NcDashboardWidget-Wkx_9xKh-CZDgtJ0S.chunk.mjs";
import { F as _ } from "./FlexSettings-BjJcoCzX.chunk.mjs";
import { u as S, _ as d, a as y } from "./StyleSettings-bhEm8wJj.chunk.mjs";
import "./NcRichText-G8kzsdwx-BXJ2t8BN.chunk.mjs";
const P = c({ __name: "UserSettingsPage", setup(x) {
  const p = S(), o = { personalSettings: { name: a("agora", "Personal preferences"), description: a("agora", "Set your personal preferences for the agora app") }, styleSettings: { name: a("agora", "Experimental styles"), description: a("agora", "Some visual styling options.") } };
  return m(() => {
    p.load();
  }), (N, b) => (g(), l(e(_), null, { default: s(() => [t(e(i), r(n(o.personalSettings)), { default: s(() => [t(e(d))]), _: 1 }, 16), t(e(i), r(n(o.styleSettings)), { default: s(() => [t(e(y))]), _: 1 }, 16)]), _: 1 }));
} }), h = f(P).use(u);
h.mount("#content_agora");
//# sourceMappingURL=agora-userSettings.mjs.map
