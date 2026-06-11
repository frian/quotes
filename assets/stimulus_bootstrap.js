import { startStimulusApp } from '@symfony/stimulus-bundle';
import ColorSelectController from './controllers/color_select_controller.js';
import ExcerptFormController from './controllers/excerpt_form_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
app.register('color-select', ColorSelectController);
app.register('excerpt-form', ExcerptFormController);
