import React, {Component} from 'react';
import './index.scss';
import {TimeLineRecord} from "./timeline-record";
import {Button, Form, Icon, TextArea, Dropdown} from 'semantic-ui-react';
import {LeadStatuses} from "@models/lead-statuses";

class TimeLine extends Component {
    state = {
        form: {
            message: '',
            status: '',
        },
        showAddNote: false
    };

    showAddNote = () => {
        this.setState({
            showAddNote: true
        });
    };

    onCancelAddNote = () => {
        this.setState({
            showAddNote: false
        });
    };

    onAddNote = () => {
        this.props.onAddNote(this.state.form)
        if(this.state.form.message){
            this.setState({
                showAddNote: false
            });
        }
    };

    onChange = (event, data) => {
        this.setState({
            form: {
                ...this.state.form,
                [data.name]: data.value
            }
        })
    };

    render() {
        const {lead, notes, leadStatuses} = this.props;
        
        return (
            <div className='freshAppTimeLine'>
                <ul>
                    { 
                        notes && notes.map((note, key) => <TimeLineRecord key={key} note={note} agency_id={lead.agency_company_id} fullname={lead.fullname}/>)
                    }
                </ul>
                {
                    (!lead.deleted_at && (this.state.showAddNote
                      ? <Form>
                          <Form.Field>
                              <TextArea name='message' onChange={this.onChange}/>
                          </Form.Field>
                          <Button.Group>
                              <Button onClick={this.onCancelAddNote}>Cancel</Button>
                              <Button.Or/>
                              <Button onClick={this.onAddNote} positive>Submit</Button>
                              <Dropdown options={leadStatuses} name='status' onChange={this.onChange} floating button
                                        className='icon' defaultValue={lead.status}/>
                          </Button.Group>
                      </Form>
                      : <Button onClick={this.showAddNote}>
                          <Icon name='flaticon stroke plus-1'/>
                          Add note
                      </Button>))
                }
            </div>
        )
    }
}

export default TimeLine;
