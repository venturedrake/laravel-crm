import './bootstrap';
import Sortable from 'sortablejs';
import Chart from 'chart.js/auto';

window.Sortable = Sortable;
window.Chart = Chart;

import Picker from 'vanilla-picker';
window.Picker = Picker;

import tinymce from 'tinymce/tinymce';
import 'tinymce/models/dom';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/skins/ui/oxide/skin.js';
import 'tinymce/skins/ui/oxide-dark/skin.js';
import 'tinymce/skins/content/default/content.js';
import 'tinymce/skins/content/dark/content.js';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/table';
import 'tinymce/plugins/quickbars';
import 'tinymce/plugins/autoresize';
window.tinymce = tinymce;