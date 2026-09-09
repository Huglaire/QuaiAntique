// Représente une route de l'application
export default class Route {

    constructor(
        path,
        title,
        view,
        script = null,
        roles = []
    ) {
        this.path = path;
        this.title = title;
        this.view = view;
        this.script = script;
        this.roles = roles;
    }
}