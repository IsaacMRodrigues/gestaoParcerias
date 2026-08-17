import Alpine from 'alpinejs';
import paletaComandos from './command-palette';

window.Alpine = Alpine;

// Registrado antes do start() para o x-data do Blade já encontrar o componente.
Alpine.data('paletaComandos', paletaComandos);

Alpine.start();

import './editor';
import './confirm-modal';
import './nav-progress';
