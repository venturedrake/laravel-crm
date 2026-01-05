import './bootstrap';
import Sortable from 'sortablejs';

window.Sortable = Sortable;

import "@melloware/coloris/dist/coloris.css";
import Coloris from "@melloware/coloris";

Coloris.init();
Coloris({el: "#coloris"});
window.Coloris = Coloris;