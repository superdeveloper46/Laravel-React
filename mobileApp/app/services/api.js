import axios from 'axios';
import * as R from 'ramda';
import appConfig from '../app.json';

class Api {
    constructor(axios) {
        this.axios = axios;
    }

    createServer() {
        return this.axios.create({
            baseURL: appConfig.serverURL,
        });
    }
}

const instance = new Api(axios).createServer();

instance.interceptors.response.use(response => response,
    (error) => {
        console.log("============> axios error", error);
        if (error.response && error.response.data) {
            const errors = R.pathOr([], ['response', 'data', 'errors'], error);
            const message = R.pathOr([], ['response', 'data', 'message'], error);
            const messages = R.reduce((acc, errors) => {
                return `${acc}\n ${errors.join(',')}`;
            }, '', R.values(errors));

            return Promise.reject(new Error(`${message} ${messages}`, error.status));
        }
        return Promise.reject(error);
    }
);

export default instance;