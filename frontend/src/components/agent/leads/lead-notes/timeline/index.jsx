import React, { Component } from 'react';
import * as moment from 'moment';
import './index.scss';
import { DATE_FORMAT } from '@constants';
import ReactHtmlParser from 'react-html-parser';

export class LeadNoteTimeLine extends Component {
    componentWillMount() { }

    render() {
        const { notes } = this.props;
        console.log(this.props)
        return (
            <div>
                {
                    notes && notes.map(note =>
                        <div className='lead-note' key={note.id}>
                            <div className='lead-note-datetime'>
                                <span className='lead-note-date'>{moment.utc(note.created_at).local().format(DATE_FORMAT)}</span>
                                <span className='lead-note-time'>{moment.utc(note.created_at).local().format('LT')}</span>
                            </div>
                            <div className='lead-note-content'>
                                <span className='agent-name'>{note.agent.name}, </span> {ReactHtmlParser(note.message)}
                            </div>
                            {
                                note.recordingUrl && (
                                    <div className='lead-note-audio'>
                                        <audio controls>
                                            <source src={note.recordingUrl} type="audio/mpeg" />
                                            Your browser does not support the audio element.
                                        </audio>
                                    </div>
                                )
                            }
                        </div>
                    )
                }
            </div>
        );
    }
}