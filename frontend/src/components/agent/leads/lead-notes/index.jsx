import React, { Component } from 'react';
import {
    BreadCrumbContainer,
    LeadsContainer,
    LeadNotesContainer,
    ReminderFormContainer
} from "@containers";
import { compose } from 'recompose';
import * as R from 'ramda';
import { Button, Icon, Form, TextArea, Dropdown } from 'semantic-ui-react';
import { LeadNoteTimeLine } from './timeline';
import { LeadReminder } from "./reminder";
import ReminderModal from "../../../@common/modals/reminder";
import ButtonGroup from "components/@common/button-group";
import { Device } from 'twilio-client';


class AgentLeadNotes extends Component {
    state = {
        showTimeline: true,
        showAdditionalInfo: false,
        showReminder: true,
        showSMSModal: false,
        lead: {
            id: '',
            email: '',
            phone: '',
            company: []
        },
        form: {
            status: '',
            message: ''
        },
        onPhone: false,
        readyToCall: false,
    };

    componentWillMount() {
        const lead = R.path(['history', 'location', 'state', 'lead'], this.props);
        this.setState({
            lead,
        });

        this.props.addBreadCrumb({
            name: 'Leads',
            path: '/companies/leads/all'
        }, true);
        this.props.addBreadCrumb({
            name: lead.fullname,
            path: '',
            active: true,
        }, false);

        this.props.loadLead(lead.company.id, lead.id, true);
        this.props.fetchTwilioTokenBy(lead.id);
    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        if (prevProps.twilioToken !== this.props.twilioToken && this.props.twilioToken) {
            Device.setup(this.props.twilioToken);
        }

        if (prevState.onPhone && !this.state.onPhone) {
            setTimeout(() => {
                this.props.loadLead(this.state.lead.company.id, this.state.lead.id, true, true);
            }, 5000)
        }
    }

    componentDidMount() {
        Device.disconnect(() => {
            this.setState({
                onPhone: false,
            });
        });
        Device.ready(() => {
            console.log('Redy to call');
            this.setState({
                readyToCall: true,
            })
        });
    }


    onCall = () => {
        const checkIsValidNumber = /^([0-9]|#|\*)+$/.test(this.props.lead.phone.replace(/[\+\-()\s]/g, ''))
        if (this.state.onPhone) {
            console.log("call_disconnected");
            Device.disconnectAll();
        } else if (this.props.twilioToken && checkIsValidNumber) {
            Device.connect({ number: this.props.lead.phone });
            this.setState({
                onPhone: true,
            });

            this.props.createLeadNote({
                message: 'initiated agent call',
                status: 'CONTACTED_CALL'
            });
        }
    }

    // onCall = () => {
    //     this.props.createLeadNote({
    //         message: 'initiated a call',
    //         status: 'CONTACTED_CALL'
    //     });
    // };

    onEmail = () => {
        this.props.createLeadNote({
            message: 'started an email conversation',
            status: 'CONTACTED_EMAIL'
        });
    }; 

    toggleTimeline = () => {
        this.setState({
            showTimeline: !this.state.showTimeline
        })
    };
    toggleAdditionalInfo = () => {
        this.setState({
            showAdditionalInfo: !this.state.showAdditionalInfo
        })
    };

    toggleReminder = () => {
        this.setState({
            showReminder: !this.state.showReminder
        })
    };

    onChange = (event, data) => {
        this.setState({
            form: {
                ...this.state.form,
                [data.name]: data.value
            }
        })
    };

    onAddNote = () => {
        this.props.createLeadNote({
            ...this.state.form,
            status: this.state.form.status ? this.state.form.status : this.props.lead.status
        });

        this.setState({
            form: {
                status: '',
                message: ''
            }
        })
    };

    onNewReminder = () => {
        const data = {
            show: true,
            leadId: this.state.lead.id,
            companyId: this.state.lead.company.id
        };
        this.props.loadForm(data);
    }

    onEditReminder = (reminder) => {
        const data = {
            show: true,
            leadId: this.state.lead.id,
            companyId: this.state.lead.company.id,
            ...reminder
        };
        this.props.loadForm(data);
    }

    onDeleteReminder = (reminder) => {
        const data = {
            show: true,
            leadId: this.state.lead.id,
            companyId: this.state.lead.company.id,
            ...reminder
        };
        this.props.deleteForm(data);
    }

    setSMSModal = () => {
        this.setState({ showSMSModal: !this.state.showSMSModal, form: { message: '' } })
    };

    onCancelSendSMS = () => {
        this.setState({ showSMSModal: false, form: { message: '' } })
    }

    onSendSMS = () => {
        if (this.state.form.message == '') {
            return;
        }

        this.props.sendSMSMessage(this.state.form)

        this.setState({ showSMSModal: false, form: { message: '' } })

        let leadId = this.state.lead.id;
        let companyId = this.state.lead.company.id;
        setTimeout(() => {
            this.props.loadLead(companyId, leadId, true, true);
        }, 5000)
    }

    onChangeSMSMessage = (event, data) => {
        this.setState({
            form: { message: data.value }
        })
    };

    render() {
        const { lead, showTimeline, showAdditionalInfo, showReminder } = this.state;
        const { leadNotes, leadStatuses, reminders } = this.props;
        return (
            <div className='AgentLeadNotes'>
                <ReminderModal />
                <div className="column">
                    <h1 className="ui left floated header mobile-app-menu">{lead.fullname}</h1>
                </div>
                <div className='lead-profile-row buttons'>
                    <Button className='remindernew-but' circular onClick={this.onNewReminder}> <Icon name='plus' /> Add reminder </Button>
                </div>
                <div className='lead-profile-row buttons'>
                    <Button as='a' href={`mailto:${lead.email}`} onClick={this.onEmail} circular>
                        <Icon className='ti-mail-forward ti' />
                    </Button>
                    <Button className={(this.state.onPhone ? 'endCall' : 'call-lead-but')} as='a' href={this.readyToCall ? `tel:${lead.phone}` : '#'} onClick={this.onCall} circular>
                        <Icon name='call' />
                    </Button>
                    <Button as='a' href='#' onClick={this.setSMSModal} circular>
                        <Icon className='ti ti-device-mobile-message' />
                    </Button>
                </div>

                {this.state.showSMSModal &&
                    <div className="lead-profile-row sms-container">
                        <Form>
                            <Form.Field>
                                <label>Send a text message</label>
                                <TextArea className="text-sms" name='message' onChange={this.onChangeSMSMessage} />
                            </Form.Field>
                            <Button.Group>
                                <Button onClick={this.onCancelSendSMS}>Cancel</Button>
                                <Button.Or />
                                <Button type="button" onClick={this.onSendSMS} positive>Send</Button>
                            </Button.Group>
                        </Form>
                    </div>
                }

                <div className='lead-info'>
                    <div className='info-header'>Subscribed for:</div>
                    <label>{lead.campaign.name}</label>
                </div>
                <div className='lead-meta'>
                    <div className='meta-header' onClick={this.toggleAdditionalInfo}>Additional info:
                        <Icon name={(showAdditionalInfo ? 'angle down' : 'angle up')} />
                    </div>
                    {
                        showAdditionalInfo && <p>{lead.metadata}</p>
                    }
                </div>
                <div className='lead-timeline'>
                    <div className='timeline-header' onClick={this.toggleTimeline}>
                        Lead Timeline
                        <Icon name={(showTimeline ? 'angle down' : 'angle up')} />
                    </div>
                    {
                        showTimeline ? <LeadNoteTimeLine notes={leadNotes} /> : null
                    }
                </div>
                <div className='lead-reminder'>
                    <div className='timeline-header' onClick={this.toggleReminder}>
                        Lead Reminder
                        <Icon name={(showReminder ? 'angle down' : 'angle up')} />
                    </div>
                    {
                        showReminder ?
                            <LeadReminder reminders={reminders} lead={lead}
                                onEdit={(reminder) => this.onEditReminder(reminder)}
                                onDelete={(reminder) => this.onDeleteReminder(reminder)} /> : null
                    }
                </div>
                <div className='addLeadNote'>
                    <Form>
                        <Form.Field>
                            <TextArea name='message' placeholder={"+ add note"} onChange={this.onChange} />
                        </Form.Field>
                        <ButtonGroup>
                            <Button onClick={this.onAddNote} positive>Submit</Button>
                            <Button.Or />
                            <Dropdown options={leadStatuses} name='status' onChange={this.onChange} floating button
                                className='icon' defaultValue={lead.status} />
                        </ButtonGroup>
                    </Form>
                </div>
            </div>
        );
    }
}


export default compose(LeadsContainer, LeadNotesContainer, BreadCrumbContainer, ReminderFormContainer)(AgentLeadNotes);