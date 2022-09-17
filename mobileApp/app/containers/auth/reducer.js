import {
    ADD_SESSION_TOKEN,
    REMOVE_SESSION_TOKEN,
    ADD_DEVICE_TOKEN,
    UPDATE_USER_PROFILE,
    REMOVE_USER_PROFILE
} from './actions';

const initState = {
    session: {
        token: null,
        refreshToken: null,
        isAuthorised: false,
    },
    profile: {
        id: null,
        name: null,
        email: null,
        phone: null,
        avatar_path: null,
        role: null,
        permissions: [],
        agencies: [],
    },
    deviceToken: null,
};

const auth = (state = initState, action) => {
    switch (action.type) {
        case ADD_SESSION_TOKEN: {
            return {
                ...state,
                session: {
                    ...action.tokenData,
                    isAuthorised: true,
                },
            };
        }
        case REMOVE_SESSION_TOKEN: {
            return {
                ...state,
                session: {
                    token: null,
                    isAuthorised: false,
                },
            };
        }
        case ADD_DEVICE_TOKEN: {
            return {
                ...state,
                deviceToken: action.deviceToken
            }
        }
        case UPDATE_USER_PROFILE: {
            return {
                ...state,
                profile: action.profile
            }
        }
        case REMOVE_USER_PROFILE: {
            return {
                ...state,
                profile: initState.profile
            }
        }
        default: {
            return state;
        }
    }
};

export default auth;
