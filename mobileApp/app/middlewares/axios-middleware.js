import axios from 'axios';
import * as R from 'ramda';

const axiosMiddleWare = ({getState}) => next => async (action) => {
    const {session} = getState().auth;
    const token = R.pathOr(false, ['token'], session);
    console.log("==============> token", token);
    if (token) {
        axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    } else {
        axios.defaults.headers.common.Authorization = null;
    }
    next(action);
};

export default axiosMiddleWare;