${?PHP}
Header("Content-type: text/html; charset=utf-8");
const O_DOC_ROOT = __DIR__;
require "${ORENA_PATH:'./O/src/EntryPoint.phps':Enter path to Orena framework}";
O_EntryPoint::processRequest();
